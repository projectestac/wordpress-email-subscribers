<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IG_ES_Subscribers_Query {

	private $last_result;
	private $last_error;
	private $last_query;

	private $args = array();

	private $defaults = array(
		'select'              => null,
		'join'                => null,
		'status'              => null,
		'status__not_in'      => null,
		'where'               => null,
		'having'              => null,
		'orderby'             => null,
		'order'               => null,
		'groupby'             => null,
		'limit'               => null,
		'offset'              => null,

		'return_ids'          => false,
		'return_count'        => false,
		'return_sql'          => false,

		'operator'            => null,
		'conditions'          => null,

		'include'             => null,
		'exclude'             => null,

		'wp_include'          => null,
		'wp_exclude'          => null,

		'fields'              => null,
		'meta'                => null,

		'lists'               => false,
		'lists__in'           => null,
		'lists__not_in'       => null,

		'unsubscribe'         => null,
		'unsubscribe__not_in' => null,

		'queue'               => false,
		'queue__not_in'       => false,

		's'                   => null,
		'search_fields'       => false,
		'strict'              => false,
		'sentence'            => false,

		'calc_found_rows'     => false,

		'signup_after'        => null,
		'signup_before'       => null,
		'confirm_after'       => null,
		'confirm_before'      => null,

		'sent'                => null,
		'sent__not_in'        => null,
		'sent_before'         => null,
		'sent_after'          => null,
		'open'                => null,
		'open__not_in'        => null,
		'open_before'         => null,
		'open_after'          => null,
		'click'               => null,
		'click__not_in'       => null,
		'click_before'        => null,
		'click_after'         => null,
		'click_link'          => null,
		'click_link__not_in'  => null,

		'sub_query_limit'     => false,
	);

	private $fields = array(
		'id',
		'email',
		'wp_user_id',
		'country_code',
	);

	private $action_fields = array(
		'_sent',
		'_sent__not_in',
		'_sent_before',
		'_sent_after',
		'_open',
		'_open__not_in',
		'_open_before',
		'_open_after',
		'_click',
		'_click__not_in',
		'_click_before',
		'_click_after',
		'_click_link',
		'_click_link__not_in',
		'_lists__in',
		'_lists__not_in',
		'_subscribed_before',
	);

	private $custom_fields = array();

	private static $_instance = null;

	public function __construct( $args = null, $campaign_id = null ) {

		if ( ! is_null( $args ) ) {
			return $this->run( $args, $campaign_id );
		}

	}
	public function __destruct() {}

	public static function get_instance( $args = null, $campaign_id = null ) {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self( $args, $campaign_id );
		}
		return self::$_instance;
	}

	public function run( $args = array(), $campaign_id = null ) {
		global $wpbd;

		$this->args = wp_parse_args( $args, $this->defaults );

		$joins  = array();
		$wheres = array();

		if ( $this->args['return_ids'] ) {
			$this->args['select'] = array( 'subscribers.id' );
		} elseif ( $this->args['return_count'] ) {
			$this->args['select'] = array( 'COUNT(DISTINCT subscribers.id)' );
			$this->args['fields'] = null;
			$this->args['meta']   = null;
		} elseif ( is_null( $this->args['select'] ) ) {
			$this->args['select'] = array();
		}

		if ( !empty( $this->args['status'] ) && ! is_null( $this->args['status'] ) && !is_array( $this->args['status'] ) ) {
			$this->args['status'] = explode( ',', $this->args['status'] );
		}

		if ( false !== $this->args['status__not_in'] && ! is_null( $this->args['status__not_in'] ) && ! is_array( $this->args['status__not_in'] ) ) {
			$this->args['status__not_in'] = explode( ',', $this->args['status__not_in'] );
		}

		if ( ! empty( $this->args['lists'] ) && is_string( $this->args['lists'] ) ) {
			$this->args['lists'] = explode( ',', $this->args['lists'] );
		}

		if ( $this->args['sent__not_in'] ) {
			$this->add_condition( '_sent__not_in', '=', $this->id_parse( $this->args['sent__not_in'] ) );
		}

		$this->args = apply_filters( 'ig_es_subscriber_query_args', $this->args );

		if ( ! empty( $this->args['queue__not_in'] ) ) {
			$join = "LEFT JOIN {$wpbd->prefix}ig_queue AS queue ON subscribers.id = queue.contact_id";
			if ( ! empty( $this->args['queue__not_in'] ) ) {
				$join .= ' AND queue.campaign_id IN (' . implode( ',', array_filter( $this->args['queue__not_in'], 'is_numeric' ) ) . ')';
			}
			$joins[] = $join;
		}

		if ( $this->args['conditions'] ) {
			$cond = array();

			foreach ( $this->args['conditions'] as $i => $and_conditions ) {

				if ( ! empty( $and_conditions ) ) {
					foreach ( $and_conditions as $j => $condition ) {

						$field    = isset( $condition['field'] ) ? $condition['field'] : ( isset( $condition[0] ) ? $condition[0] : null );
						$operator = isset( $condition['operator'] ) ? $condition['operator'] : ( isset( $condition[1] ) ? $condition[1] : null );
						$value    = isset( $condition['value'] ) ? $condition['value'] : ( isset( $condition[2] ) ? $condition[2] : null );
						// something is not set => skip
						if ( is_null( $field ) || is_null( $operator ) ) {
							unset( $this->args['conditions'][ $i ][ $j ] );
							continue;
						}
						// requires campaign to be sent
						if ( in_array( $field, array( '_open__not_in', '_click__not_in' ) ) ) {
							$this->add_condition( '_sent', '=', $value );
						}
					}
				}
			}
		}

		if ( ! empty( $this->args['conditions'] ) ) {
			foreach ( $this->args['conditions'] as $i => $and_conditions ) {

				$sub_cond = array();

				if ( ! empty( $and_conditions ) ) {
					foreach ( $and_conditions as $j => $condition ) {

						$field    = isset( $condition['field'] ) ? $condition['field'] : ( isset( $condition[0] ) ? $condition[0] : null );
						$operator = isset( $condition['operator'] ) ? $condition['operator'] : ( isset( $condition[1] ) ? $condition[1] : null );
						$value    = isset( $condition['value'] ) ? $condition['value'] : ( isset( $condition[2] ) ? $condition[2] : null );

						if ( ! in_array( $field, $this->action_fields, true ) ) {
							$sub_cond[] = $this->get_condition( $field, $operator, $value );
						} else {

							if ( '_sent_before' !== $field ) {
								$value = $this->remove_empty_values( $value );
							}

							$alias = 'actions' . $field . '_' . $i . '_' . $j;

							if ( '_lists__in' === $field ) {

								if ( $value ) {
									$sub_cond[] = "lists_subscribers.contact_id IN ( SELECT contact_id FROM {$wpbd->prefix}ig_lists_contacts WHERE list_id IN (" . implode( ',', array_filter( $value, 'is_numeric' ) ) . ") AND status IN( 'subscribed', 'confirmed' ) )";
								}
							} elseif ( '_lists__not_in' === $field ) {

								if ( $value ) {
									$sub_cond[] = "lists_subscribers.contact_id NOT IN ( SELECT contact_id FROM {$wpbd->prefix}ig_lists_contacts WHERE list_id IN (" . implode( ',', array_filter( $value, 'is_numeric' ) ) . ') )';
								} else {
									$sub_cond[] = "lists_subscribers.contact_id NOT IN ( SELECT contact_id FROM {$wpbd->prefix}ig_lists_contacts WHERE list_id <> 0 )";
								}
							} elseif ( 0 === strpos( $field, '_sent' ) ) {

								$join = "LEFT JOIN {$wpbd->prefix}ig_actions AS $alias ON $alias.type = " . IG_MESSAGE_SENT . " AND subscribers.id = $alias.contact_id";
								if ( ( '_sent' === $field || '_sent__not_in' === $field ) ) {
									if ( $value ) {
										$join .= " AND $alias.campaign_id IN (" . implode( ',', array_filter( $value, 'is_numeric' ) ) . ')';
									} else {
										$join .= " AND $alias.campaign_id IS NOT NULL AND $alias.campaign_id <> 0";
									}
								}

								if ( '_sent' === $field ) {
									$sub_cond[] = "$alias.contact_id IS NOT NULL";
								} elseif ( '_sent__not_in' === $field ) {
									$sub_cond[] = "$alias.contact_id IS NULL";
								} elseif ( '_sent_before' === $field ) {
									$sub_cond[] = "$alias.created_at <= " . $this->get_timestamp( $value );
								} elseif ( '_sent_after' === $field ) {
									$sub_cond[] = "$alias.created_at >= " . $this->get_timestamp( $value );
								}

								$joins[] = $join;

							} elseif ( 0 === strpos( $field, '_open' ) ) {

								$join = "LEFT JOIN {$wpbd->prefix}ig_actions AS $alias ON $alias.type = " . IG_MESSAGE_OPEN . " AND subscribers.id = $alias.contact_id";
								if ( ( '_open' === $field || '_open__not_in' === $field ) ) {
									if ( $value ) {
										$join .= " AND $alias.campaign_id IN (" . implode( ',', array_filter( $value, 'is_numeric' ) ) . ')';
									} else {
										$join .= " AND $alias.campaign_id IS NOT NULL AND $alias.campaign_id <> 0";
									}
								}

								if ( '_open' === $field ) {
									$sub_cond[] = "$alias.contact_id IS NOT NULL";
								} elseif ( '_open__not_in' === $field ) {
									$sub_cond[] = "$alias.contact_id IS NULL";
								} elseif ( '_open_before' === $field ) {
									$sub_cond[] = "$alias.timestamp <= " . $this->get_timestamp( $value );
								} elseif ( '_open_after' === $field ) {
									$sub_cond[] = "$alias.timestamp >= " . $this->get_timestamp( $value );
								}

								$joins[] = $join;

							} elseif ( 0 === strpos( $field, '_click' ) ) {

								$join = "LEFT JOIN {$wpbd->prefix}ig_actions AS $alias ON $alias.type = " . IG_LINK_CLICK . " AND subscribers.id = $alias.contact_id";

								if ( ( '_click' === $field || '_click__not_in' === $field ) ) {
									if ( $value ) {
										$join .= " AND $alias.campaign_id IN (" . implode( ',', array_filter( $value, 'is_numeric' ) ) . ')';
									} else {
										$join .= " AND $alias.campaign_id IS NOT NULL AND $alias.campaign_id <> 0";
									}
								} elseif ( '_click_link' === $field || '_click_link__not_in' === $field ) {
									$join     .= " AND $alias.link_id = {$alias}{$field}.ID";
									$campaigns = array();
									foreach ( $value as $k => $v ) {
										if ( is_numeric( $v ) ) {
											$campaigns[] = $v;
											unset( $value[ $k ] );
										}
									}
									$campaigns = array_filter( $campaigns );
									if ( ! empty( $campaigns ) ) {
										$join .= " AND $alias.campaign_id IN (" . implode( ',', array_filter( $campaigns, 'is_numeric' ) ) . ')';
									}
									$joins[] = "LEFT JOIN {$wpbd->prefix}ig_links AS {$alias}{$field} ON {$alias}{$field}.link IN ('" . implode( "','", $value ) . "')";
								}

								if ( '_click' === $field ) {
									$sub_cond[] = "$alias.contact_id IS NOT NULL";
								} elseif ( '_click__not_in' === $field ) {
									$sub_cond[] = "$alias.contact_id IS NULL";
								} elseif ( '_click_before' === $field ) {
									$sub_cond[] = "$alias.timestamp <= " . $this->get_timestamp( $value );
								} elseif ( '_click_after' === $field ) {
									$sub_cond[] = "$alias.timestamp >= " . $this->get_timestamp( $value );
								} elseif ( '_click_link' === $field ) {
									$sub_cond[] = "$alias.contact_id IS NOT NULL";
								} elseif ( '_click_link__not_in' === $field ) {
									$sub_cond[] = "$alias.contact_id IS NULL";
								}

								$joins[] = $join;
							}
						}
					}
				}
				$sub_cond = array_filter( $sub_cond );
				if ( ! empty( $sub_cond ) ) {
					$cond[] = '( ' . implode( ' OR ', $sub_cond ) . ' )';
				}
			}
		}

		if ( ! empty( $cond ) ) {
			$wheres[] = 'AND ( ' . implode( ' AND ', $cond ) . ' )';
		}

		$joins[] = "LEFT JOIN {$wpbd->prefix}ig_lists_contacts AS lists_subscribers ON subscribers.id = lists_subscribers.contact_id";
		
		// Added where clause for including status if only sent in parameters
		if ( !empty( $this->args['status']) ) {
			$wheres[] = "AND lists_subscribers.status IN( '" . implode("', '", esc_sql( $this->args['status'] ) ) . "' ) ";
		}
		
		if ( ! empty( $this->args['subscriber_status'] ) ) {
			$wheres[] = "AND subscribers.status IN( '" . implode("', '", esc_sql( $this->args['subscriber_status'] ) ) . "' )";
		}

		if ( ! is_bool( $this->args['lists'] ) ) {
			// unassigned members if NULL
			if ( is_array( $this->args['lists'] ) ) {
				$this->args['lists'] = array_filter( $this->args['lists'], 'is_numeric' );
				if ( empty( $this->args['lists'] ) ) {
					$wheres[] = 'AND lists_subscribers.list_id = 0';
				} else {
					$wheres[] = 'AND lists_subscribers.list_id IN (' . implode( ',', esc_sql( $this->args['lists'] ) ) . ')';
				}
				$wheres[] = "AND lists_subscribers.status IN( 'subscribed', 'confirmed' )";
				// not in any list
			} elseif ( -1 == $this->args['lists'] ) {
				$wheres[] = 'AND lists_subscribers.list_id IS NULL';
				// ignore lists
			}
		}

		if ( ! empty( $this->args['queue__not_in'] ) ) {
			$wheres[] = 'AND queue.contact_id IS NULL';
		}

		if ( $this->args['where'] ) {
			$wheres[] = 'AND ( ' . implode( ' AND ', array_unique( $this->args['where'] ) ) . " )\n";
		}

		if ( $this->args['orderby'] && ! $this->args['return_count'] ) {

			$ordering = isset( $this->args['order'][0] ) ? strtoupper( $this->args['order'][0] ) : 'ASC';
			$orders   = array();

			foreach ( $this->args['orderby'] as $i => $orderby ) {
				$ordering = isset( $this->args['order'][ $i ] ) ? strtoupper( $this->args['order'][ $i ] ) : $ordering;
				if ( in_array( $orderby, $this->fields ) ) {
					$orders[] = "subscribers.$orderby $ordering";
				} else {

					$orders[] = "$orderby $ordering";
				}
			}
		}

		$select  = 'SELECT';
		$select .= ' ' . implode( ', ', $this->args['select'] );

		$from = "FROM {$wpbd->prefix}ig_contacts AS subscribers";
		$join = '';
		if ( ! empty( $joins ) ) {
			$join = implode( "\n ", array_unique( $joins ) );
		}

		$where = '';
		if ( ! empty( $wheres ) ) {
			$where = 'WHERE 1=1 ' . implode( "\n  ", array_unique( $wheres ) );
		}

		$groupby = '';
		if ( ! empty( $this->args['groupby'] ) ) {
			$groupby = 'GROUP BY ' . $this->args['groupby'] . '';
		}

		$having = '';
		if ( ! empty( $this->args['having'] ) ) {
			$having = 'HAVING ' . implode( ' AND ', array_unique( $this->args['having'] ) );
		}

		$order = '';
		if ( ! empty( $orders ) ) {
			$order = 'ORDER BY ' . implode( ', ', array_unique( $orders ) );
		}

		$sql  = apply_filters( 'ig_es_subscriber_query_sql_select', $select, $this->args, $campaign_id ) . "\n";
		$sql .= ' ' . apply_filters( 'ig_es_subscriber_query_sql_from', $from, $this->args, $campaign_id ) . "\n";
		$sql .= ' ' . apply_filters( 'ig_es_subscriber_query_sql_join', $join, $this->args, $campaign_id ) . "\n";
		$sql .= ' ' . apply_filters( 'ig_es_subscriber_query_sql_where', $where, $this->args, $campaign_id ) . "\n";
		$sql .= ' ' . apply_filters( 'ig_es_subscriber_query_sql_groupby', $groupby, $this->args, $campaign_id ) . "\n";
		$sql .= ' ' . apply_filters( 'ig_es_subscriber_query_sql_having', $having, $this->args, $campaign_id ) . "\n";
		$sql .= ' ' . apply_filters( 'ig_es_subscriber_query_sql_order', $order, $this->args, $campaign_id ) . "\n";

		$sql = trim( $sql );

		$sql = apply_filters( 'ig_es_subscriber_query_sql', $sql, $this->args, $campaign_id );

		if ( $this->args['return_sql'] ) {
			$result            = $sql;
			$this->last_query  = $sql;
			$this->last_error  = null;
			$this->last_result = null;
		} else {
			if ( $this->args['return_count'] ) {
				$result = (int) $wpbd->get_var( $sql );
			} else {

				$sub_query_limit  = $this->args['sub_query_limit'] ? (int) $this->args['sub_query_limit'] : false;
				$sub_query_offset = 0;
				$limit_sql        = '';
				$result           = array();
				$round            = 0;

				do {

					// limit is not set explicitly => do sub queries
					if ( $sub_query_limit && ! $this->args['limit'] ) {
						$sub_query_offset = $sub_query_limit * ( $round++ );
						$limit_sql        = ' LIMIT ' . $sub_query_offset . ', ' . $sub_query_limit;
					}

					// get sub query
					if ( $this->args['return_ids'] ) {
						$sub_result = $wpbd->get_col( $sql . $limit_sql );
					} else {
						$sub_result = $wpbd->get_results( $sql . $limit_sql );
					}

					$result = array_merge( $result, $sub_result );

					if ( ! $sub_query_limit || ! $this->args['limit'] && count( $sub_result ) < $sub_query_limit ) {
						break;
					}
				} while ( ! empty( $sub_result ) );

				unset( $sub_result );
			}

			$this->last_query  = $sql;
			$this->last_error  = $wpbd->last_error;
			$this->last_result = $result;
		}

		return $result;
	}

	private function get_condition( $field, $operator, $value ) {

		if ( is_array( $value ) ) {
			$x = array();
			foreach ( $value as $entry ) {
				$x[] = $this->get_condition( $field, $operator, $entry );
			}

			return '(' . implode( ' OR ', array_unique( $x ) ) . ')';
		}

		// sanitation
		$field    = esc_sql( $field );
		$value    = addslashes( stripslashes( esc_sql( $value ) ) );
		$operator = $this->get_field_operator( $operator );

		$is_empty = '' === $value;
		$extra    = '';
		$positive = false;
		$f        = false;

		switch ( $operator ) {
			case '=':
			case 'is':
				$positive = true;
				// no break
			case '!=':
			case 'is_not':
				$f = "subscribers.$field";
				$c = $f . ' ' . ( $positive ? '=' : '!=' ) . " '$value'";
				if ( $is_empty && $positive || ! $positive ) {
					$c = '( ' . $c . ' OR ' . $f . ' IS NULL )';
				}

				return $c;
				break;

			case '<>':
			case 'contains':
				$positive = true;
				// no break
			case '!<>':
			case 'contains_not':
				$value = addcslashes( $value, '_%\\' );
				$value = "'%$value%'";

				$f = "subscribers.$field";
				$c = $f . ' ' . ( $positive ? 'LIKE' : 'NOT LIKE' ) . " $value";
				if ( $is_empty && $positive || ! $positive ) {
					$c = '( ' . $c . ' OR ' . $f . ' IS NULL )';
				}

				return $c;
				break;

			case '^':
			case 'begin_with':
				$value = addcslashes( $value, '_%\\' );
				$value = "'$value%'";

				$f = "subscribers.$field";
				$c = $f . " LIKE $value";

				return $c;
				break;

			case '$':
			case 'end_with':
				$value = addcslashes( $value, '_%\\' );

				$value = "'%$value'";

				$f = "subscribers.$field";

				$c = $f . " LIKE $value";

				return $c;
				break;

			case '>=':
			case 'is_greater_equal':
			case '<=':
			case 'is_smaller_equal':
				$extra = '=';
				// no break
			case '>':
			case 'is_greater':
			case '<':
			case 'is_smaller':
				$f     = "subscribers.$field";
				$is_numeric = is_numeric( $value );
				if ( $is_numeric ) {
					$value = (float) $value;
				} else {
					$value = ! empty( $value ) ? "'$value'" : '';
				}

				$c = $f . ' ' . ( in_array( $operator, array( 'is_greater', 'is_greater_equal', '>', '>=' ) ) ? '>' . $extra : '<' . $extra ) . " $value";

				return $c;
				break;

			case '%':
			case 'pattern':
				$positive = true;
				// no break
			case '!%':
			case 'not_pattern':
				$f = "subscribers.$field";
				if ( $is_empty ) {
					$value = '.';
				}

				if ( ! $positive ) {
					$extra = 'NOT ';
				}

				$c = $f . ' ' . $extra . "REGEXP '$value'";
				if ( $is_empty && $positive || ! $positive ) {
					$c = '( ' . $c . ' OR ' . $f . ' IS NULL )';
				}

				return $c;
				break;

		}

	}

	private function get_field_operator( $operator ) {

		switch ( $operator ) {
			case '=':
				return 'is';
			case '!=':
				return 'is_not';
			case '<>':
				return 'contains';
			case '!<>':
				return 'contains_not';
			case '^':
				return 'begin_with';
			case '$':
				return 'end_with';
			case '>=':
				return 'is_greater_equal';
			case '<=':
				return 'is_smaller_equal';
			case '>':
				return 'is_greater';
			case '<':
				return 'is_smaller';
			case '%':
				return 'pattern';
			case '!%':
				return 'not_pattern';
		}

		return $operator;

	}

	private function add_condition( $field, $operator, $value ) {
		$condition = array(
			'field'    => $field,
			'operator' => $operator,
			'value'    => $value,
		);

		if ( ! $this->args['conditions'] ) {
			$this->args['conditions'] = array();
		}

		array_unshift( $this->args['conditions'], array( $condition ) );

	}

	private function remove_empty_values( $value ) {
		if ( ! is_array( $value ) ) {
			$value = explode( ',', $value );
		}
		$campaign_ids = array_filter( array_unique( $value ) );
		return $campaign_ids;
	}

	private function get_timestamp( $value, $format = null ) {
		$timestamp = is_numeric( $value ) ? strtotime( '@' . $value ) : strtotime( '' . $value );
		if ( is_numeric( $value ) ) {
			$timestamp = (int) $value;
		} else {
			return false;
		}

		if ( is_null( $format ) ) {
			return $timestamp;
		}

		return gmdate( $format, $timestamp );
	}

	private function id_parse( $ids ) {

		if ( empty( $ids ) ) {
			return $ids;
		}

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$return = array();
		foreach ( $ids as $id ) {
			if ( is_numeric( $id ) ) {
				$return[] = $id;
			} elseif ( false !== strpos( $id, '-' ) ) {
				$splitted = explode( '-', $id );
				$min      = min( $splitted );
				$max      = max( $splitted );
				for ( $i = $min; $i <= $max; $i++ ) {
					$return[] = $i;
				}
			}
		}

		return array_values( array_unique( $return ) );

	}
}

