<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

class es_cls_templates {
	public static function es_template_select($id = 0) {

		$arrRes = array();
		if($id > 0) {
			// if(empty($arrRes)){
				$es_tmpl_post = get_post($id, ARRAY_A);
				$arrRes = array(
					'es_templ_id' 		=> $es_tmpl_post['ID'],
					'es_templ_heading' 	=> $es_tmpl_post['post_title'],
					'es_templ_body' 	=> $es_tmpl_post['post_content'],
					'es_templ_status' 	=> $es_tmpl_post['post_status'],
					'es_email_type' 	=> get_post_meta($id, 'es_template_type', true)
				);
			// }
		}

		return $arrRes;
	}

	public static function es_template_select_type($type = "Newsletter") {

		$arrRes = array();

		// New Custom Post Type push
		$es_template = get_posts(array(
										'post_type'		 => array('es_template'),
										'post_status'	 => 'publish',
										'posts_per_page' => -1,
										'fields'		 => 'ids',
										'post_status'	 => 'publish',
										'meta_query' 	 => array(
											  						array(
																			'key'     => 'es_template_type',
																			'value'   => $type,
																			'compare' => '='
																	)
															)
									)
								);

		foreach ($es_template as $id) {
			$es_post_thumbnail = get_the_post_thumbnail( $id );
			$es_templ_thumbnail = ( !empty( $es_post_thumbnail ) ) ? get_the_post_thumbnail( $id, array('200','200') ) : '<img src="'.ES_URL.'images/envelope.png" />';
			$tmpl = array(
					'es_templ_id' => $id,
					'es_templ_heading' =>  get_the_title($id),
					'es_templ_thumbnail' => $es_templ_thumbnail
				);
			$arrRes[] = $tmpl;
		}

		return $arrRes;
	}

}