<?php

if ( ! class_exists( 'ES_Campaign' ) ) {

	/**
	 * Class to handle single campaign options
	 * 
	 * @class ES_Campaign
	 */
	class ES_Campaign {
	
		/**
		 * Campaign id
		 *
		 * @since 4.4.1
		 * @var int
		 */
		public $id;
	
		/**
		 * Workflow slug
		 *
		 * @since 4.4.1
		 * @var string
		 */
		public $slug;
	
		/**
		 * Workflow name(slug)
		 *
		 * @since 4.4.1
		 * @var string
		 */
		public $name;
	
		/**
		 * Workflow trigger object
		 *
		 * @since 4.4.1
		 * @var ES_Workflow_Trigger
		 */
		public $type;
	
		/**
		 * Workflow actions
		 *
		 * @since 4.4.1
		 * @var ES_Workflow_Actions[]
		 */
		public $parent_id;
	
		/**
		 * Workflow data absctraction class object
		 *
		 * @since 4.4.1
		 * @var ES_Workflow_Data_Layer
		 */
		public $parent_type;
	
		/**
		 * Workflow status
		 *
		 * @since 4.4.1
		 * @var integer
		 */
		public $subject;
	
		/**
		 * Workflow trigger name
		 *
		 * @since 4.4.1
		 * @var string
		 */
		public $body;
	
		/**
		 * Workflow trigger options
		 *
		 * @since 4.4.1
		 * @var array
		 */
		public $from_name;
	
		/**
		 * Workflow rules
		 *
		 * @since 4.4.1
		 * @var array
		 */
		public $from_email;
	
		/**
		 * Workflow meta data
		 *
		 * @since 4.4.1
		 * @var array
		 */
		public $reply_to_email;
	
		/**
		 * Workflow priority
		 *
		 * @since 4.4.1
		 * @var integer
		 */
		public $categories;

		/**
		 * Workflow priority
		 *
		 * @since 4.4.1
		 * @var integer
		 */
		public $list_ids;

		/**
		 * Workflow priority
		 *
		 * @since 4.4.1
		 * @var integer
		 */
		public $base_template_id;

		/**
		 * Workflow priority
		 *
		 * @since 4.4.1
		 * @var integer
		 */
		public $meta;
	
		/**
		 * Workflow creation date/time
		 *
		 * @since 4.4.1
		 * @var string
		 */
		public $created_at;
	
		/**
		 * Workflow last update date/time
		 *
		 * @since 4.4.1
		 * @var string
		 */
		public $updated_at;

		/**
		 * Flag to check whether valid workflow object or not
		 *
		 * @since 4.4.1
		 * @var bool
		 */
		public $exists = false;
	
		/**
		 * Added Logger Context
		 *
		 * @since 4.2.0
		 * @var array
		 */
		public $logger_context = array(
			'source' => 'ig_es_workflow',
		);
	
		/**
		 * Class constructor
		 * 
		 * @param $post mixed (object or post ID)
		 */
		public function __construct( $campaign = null ) {
	
			if ( is_numeric( $campaign ) ) {
				// Get from id
				$campaign = self::get_instance( $campaign );
			}
	
			if ( is_object( $campaign ) ) {
				$this->exists           = true;
				$this->id               = $campaign->id;
				$this->slug             = $campaign->slug;
				$this->name             = $campaign->name;
				$this->type             = $campaign->type;
				$this->parent_id        = $campaign->parent_id;
				$this->parent_type      = $campaign->parent_type;
				$this->subject          = $campaign->subject;
				$this->body             = $campaign->body;
				$this->from_name        = $campaign->from_name;
				$this->from_email       = $campaign->from_email;
				$this->reply_to_email   = $campaign->reply_to_email;
				$this->categories       = $campaign->categories;
				$this->list_ids         = $campaign->list_ids;
				$this->base_template_id = $campaign->base_template_id;
				$this->status           = $campaign->status;
				$this->meta             = maybe_unserialize( $campaign->meta );
				$this->created_at       = $campaign->created_at;
				$this->updated_at       = $campaign->updated_at;
			}
		}
	
		/**
		 * Retrieve ES_Workflow instance.
		 *
		 * @global wpdb $wpdb WordPress database abstraction object.
		 *
		 * @param int $campaign_id Worfklow ID.
		 * @return ES_Workflow|false Worfklow object, false otherwise.
		 */
		public static function get_instance( $campaign_id = 0 ) {
	
			$campaign_id = ES_Clean::id( $campaign_id );
			if ( ! $campaign_id ) {
				return false;
			}

			$_campaign = ES()->campaigns_db->get( $campaign_id, 'object' );
			if ( ! $_campaign ) {
				return false;
			}
	
			return new ES_Campaign( $_campaign );
		}
	
		/**
		 * Get workflow id
		 * 
		 * @return int
		 */
		public function get_id() {
			return $this->id ? ES_Clean::id( $this->id ) : 0;
		}
	
		/**
		 * Get workflow slug
		 * 
		 * @return string
		 */
		public function get_slug() {
			return $this->slug;
		}

		 /**
		 * Get workflow name
		 * 
		 * @return string
		 */
		public function get_name() {
			return $this->name;
		}

		 /**
		 * Get workflow type
		 * 
		 * @return string
		 */
		public function get_type() {
			return $this->type;
		}

		 /**
		 * Get workflow parent_id
		 * 
		 * @return string
		 */
		public function get_parent_id() {
			return $this->parent_id;
		}

		 /**
		 * Get workflow parent_type
		 * 
		 * @return string
		 */
		public function get_parent_type() {
			return $this->parent_type;
		}

		 /**
		 * Get workflow subject
		 * 
		 * @return string
		 */
		public function get_subject() {
			return $this->subject;
		}

		 /**
		 * Get workflow body
		 * 
		 * @return string
		 */
		public function get_body() {
			return $this->body;
		}

		 /**
		 * Get workflow from_name
		 * 
		 * @return string
		 */
		public function get_from_name() {
			return $this->from_name;
		}

		 /**
		 * Get workflow from_email
		 * 
		 * @return string
		 */
		public function get_from_email() {
			return $this->from_email;
		}

		 /**
		 * Get workflow reply_to_email
		 * 
		 * @return string
		 */
		public function get_reply_to_email() {
			return $this->reply_to_email;
		}

		 /**
		 * Get workflow categories
		 * 
		 * @return string
		 */
		public function get_categories() {
			return $this->categories;
		}

		 /**
		 * Get workflow list_ids
		 * 
		 * @return string
		 */
		public function get_list_ids() {
			return $this->list_ids;
		}

		 /**
		 * Get workflow base_template_id
		 * 
		 * @return string
		 */
		public function get_base_template_id() {
			return $this->base_template_id;
		}

		/**
		 * Get creation date/time of workflow.
		 * 
		 * @return string
		 */
		public function get_date_created() {
			return $this->created_at;
		}
	
		/**
		 * Get scheduled time to run workflow.
		 * 
		 * @return string
		 */
		public function get_scheduled_time() {
			return ES_Clean::string( $this->get_meta( 'scheduled_time' ) );
		}

		/**
		 * Is workflow active.
		 *
		 * @return bool
		 */
		public function is_active() {
			if ( ! $this->exists ) {
				return false;
			}
	
			return $this->get_status() === 'active';
		}
	
		/**
		 * Get workflow status.
		 *
		 * Possible statuses are active|inactive|trash
		 *
		 * @since 4.6
		 *
		 * @return string
		 */
		public function get_status() {
			$status = $this->status;
			if ( 1 === $status ) {
				$status = 'active';
			} elseif ( 0 === $status ) {
				$status = 'inactive';
			}
	
			return $status;
		}
	
	
		/**
		 * Update worflow status.
		 * 
		 * @param string $status active|inactive i.e 1|0
		 */
		public function update_status( $status ) {
	
			if ( 'active' === $status ) {
				$campaign_status = 1;
			} elseif ( 'inactive' === $status ) {
				$campaign_status = 0;
			} else {
				$campaign_status = $status;
			}
	
			$campaign_id = $this->get_id();
	
			$status_updated = ES()->campaigns_db->update_status( $campaign_id, $campaign_status );
	
			return $status_updated;
		}
	
		/**
		 * Get workflow meta data from meta key.
		 * 
		 * @param $key
		 * @return mixed
		 */
		public function get_meta( $key ) {
			return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : '';
		}
	}
}
