<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<style type="text/css">
    body {
        background-color: transparent;
       font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
        line-height: 1.2em;
    }

    .about-wrap.es {
        max-width: 100%;
    }

    .wrap.about-wrap {
        margin: 25px 70px 0 70px;
    }

    .es_main_heading {
        font-size: 2em;
        color: #5e646a;
        text-align: center;
        font-weight: 600;
        margin: 1em auto;
    }

    .es_pro_heading {
        font-size: 1.5em;
        color: #5e646a;
        text-align: center;
        font-weight: 600;
    }

    .row {
        padding: 1em !important;
        margin: 1em !important;
        clear: both;
        position: relative;
    }

    .es_featured_column_container {
        display: -webkit-box;
        display: -webkit-flex;
        display: -ms-flexbox;
        display: flex;
        max-width: 900px;
        margin-right: auto;
        margin-left: auto;
        padding-right: 2em;
        padding-left: 2em;
    }

    .column_one_third {
        width: 40%;
    }

    .column_one_half {
        width: 45%;
    }

    .column {
        padding: 1em;
        margin: 0 1em;
        background-color: #fff;
        border: 1px solid rgba(0, 0, 0, 0.1);
        text-align: center;
        color: rgba(0, 0, 0, 0.75);
    }

    .last {
        margin-right: 0;
    }

    .last.es_save_price:before {
        content: '';
        margin-top: -2.1em;
        margin-left: 9em;
        position: absolute;
        z-index: 50;
    }

    .es_monthly_price,
    .es_yearly_price {
        margin: 1.5em 0;
        color: #1e73be;
        font-size: 1em;
    }

    .es_monthly_price b,
    .es_yearly_price b {
        font-size: 2em;
        color: #1a72bf
    }

    .es_monthly .es_button,
    .es_monthly .es_button:hover {
        background: #1a72bf;
    }

    .es_max .es_button,
    .es_max .es_button:hover {
        background: #f5873f;
    }

    .es_button {
        background: #03a025;
        border-color: #03a025;
        color: #FFFFFF !important;
        padding: 15px 32px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin: 4px 2px;
        cursor: pointer;
    }

    .es_button:hover {
        background: #00870c;
        border-color: #00870c;
        color: #FFFFFF;
    }

    .es_pro_feature {
        text-align: center;
        font-size: 2em;
        font-weight: 600;
        line-height: 1.2em;
    }

    .pricing__headline {
        font-size: 1.5em;
        font-weight: 600;
        color: #555;
        text-align: center;
        line-height: 1.5em;
        margin: 0 auto 1em;
    }

    aside {
        display: block;
        padding: 1.41575em;
        margin: 1.618em auto;
        width: 15%;
        margin: 0 auto;
        position: relative;
        color: rgba(0, 0, 0, 0.95);
        text-align: center;
    }

    li {
        text-align: left;
    }

    ul.checkmark li {
        list-style-type: none;
        padding: 0.25em 0 0 2.35em;
        position: relative;
        margin-bottom: 0.618em;
    }

    ul.checkmark li:before {
        content: " ";
        display: block;
        position: absolute;
        top: .5em;
        border: solid 0.618em rgba(68, 173, 105, 0.2);
        border-radius: 0.618em;
        left: 0.5em;
    }

    ul.checkmark li:after {
        content: " ";
        display: block;
        position: absolute;
        top: 0.5em;
        width: 0.25em;
        height: 0.6em;
        border: solid #44ad69;
        border-width: 0 0.15em 0.15em 0;
        left: 1em;
        margin-top: 0.1em;
        transform: rotate(50deg);
    }
    .dashicons-es{
        width: 10%;
        display: inline-block;
        vertical-align: middle;
    }
    .es_block_headline{
        width: 80%;
        display: inline;
        color: #f55764 !important;
    }
</style>

<div class="wrap about-wrap es">
    <div class="es_pro_heading"><?php _e( 'Get more with Email Subscribers Pro / Max', 'email-subscribers' ); ?></div>
    <div class="row">
        <div class="es_featured_column_container">
            <div class="column column_one_third es_monthly">
                <div class="es_plan"><h4>Pro - Monthly</h4> (Single Site)</div>
                <div class="es_monthly_price"><b>$19.99/</b><?php _e( 'month', 'email-subscribers' ); ?></div>
                <a href="https://www.icegram.com/?buy-now=39043&amp;qty=1&amp;coupon=&amp;with-cart=1&amp;page=5&utm_source=es&utm_medium=in_app_pricing&utm_campaign=es_monthly" target="_blank" rel="noopener" class="es_button"><?php _e( 'Get Pro Monthly', 'email-subscribers' ); ?></a>
            </div>
            <div class="column column_one_third">
                <div class="es_plan"><h4>Pro - Annual</h4> (Single Site)</div>
                <div class="es_yearly_price"><strike>$199</strike></b> <b>$129/</b><?php _e( 'year', 'email-subscribers' ); ?></div>
                <a href="https://www.icegram.com/?buy-now=39944&amp;qty=1&amp;coupon=&amp;with-cart=1&amp;page=5utm_source=es&utm_medium=in_app_pricing&utm_campaign=es_yearly" target="_blank" rel="noopener" class="es_button"><?php _e( 'Get Pro Annual', 'email-subscribers' ); ?></a>
            </div>
            <div class="column column_one_third last es_save_price es_max">
                <div class="es_plan"><h4>Max - Multi Site, Annual</h4> (3 Sites)</div>
                <div class="es_yearly_price"><strike>$597</strike></b> <b>$177/</b><?php _e( 'year', 'email-subscribers' ); ?></div>
                <a href="https://www.icegram.com/?buy-now=404335&amp;qty=1&amp;coupon=&amp;with-cart=1&amp;page=5utm_source=es&utm_medium=in_app_pricing&utm_campaign=es_yearly" target="_blank" rel="noopener" class="es_button"><?php _e( 'Get Max Annual', 'email-subscribers' ); ?></a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="pricing__headline"><?php _e( 'All features of Email Subscribers free plugin + everything below:', 'email-subscribers' ); ?></div>
        <div class="es_featured_column_container">
            <div class="column column_one_half">
                <div style="text-align: center;"><i class="dashicons dashicons-es dashicons-shield"></i><h4 class="es_block_headline"><strong><?php _e( 'Protect your list from bot attacks', 'email-subscribers' ); ?></strong></h4></div>
                <p style="text-align: left;"><?php _e( 'Use ', 'email-subscribers' ); ?><strong><?php _e( 'captcha', 'email-subscribers' ); ?></strong> <?php _e( 'to protect your email list from bots. The simple maths captcha helps identifying bots from humans and eliminates spam signups.', 'email-subscribers' ); ?></p>
            </div>
            <div class="column column_one_half last">
                <div style="text-align: center;"><i class="dashicons dashicons-es dashicons-yes"></i><h4 class="es_block_headline"><strong><?php _e( 'Check email status & increase email success rate', 'email-subscribers' ); ?></strong></h4></div>
                <p style="text-align: left;"><?php _e( 'Double check the status of the emails addresses and increase ', 'email-subscribers' ); ?><strong><?php _e( 'email success rate', 'email-subscribers' ); ?></strong> <?php _e( 'of your email campaign. ', 'email-subscribers' ); ?></p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="es_featured_column_container">
            <div class="column column_one_half">
                <div style="text-align: center;"><i class="dashicons dashicons-es dashicons-email"></i><h4 class="es_block_headline"><strong><?php _e( 'Fullproof email deliverability', 'email-subscribers' ); ?></strong></h4></div>
                <p style="text-align: left;"><?php _e( 'Reduce the risk of emails ending in trash or spam. Increase email deliverability by double checking emails for their ', 'email-subscribers' ); ?><strong><?php _e( 'spam score', 'email-subscribers' ); ?></strong> <?php _e( 'before hitting send.', 'email-subscribers' ); ?></p>
            </div>
            <div class="column column_one_half last">
                <div style="text-align: center;"><i class="dashicons dashicons-es dashicons-chart-bar"></i><h4 class="es_block_headline"><strong><?php _e( 'Track email leads in Google', 'email-subscribers' ); ?></strong></h4></div>
                <p style="text-align: left;"><?php _e( 'Insert ', 'email-subscribers' ); ?><strong><?php _e( 'UTM tracking', 'email-subscribers' ); ?></strong> <?php _e( 'in all your email CTA’s and track the effectiveness of your emails directly within Google. Know which/ how many leads landed up from your emails and tweak emails for better performance.',
						'email-subscribers' ); ?></p>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="es_featured_column_container">
            <div class="column column_one_half">
                <div style="text-align: center;"><i class="dashicons dashicons-es dashicons-media-text"></i><h4 class="es_block_headline"><strong><?php _e( 'Save time, use readymade email templates', 'email-subscribers' ); ?></strong></h4></div>
                <p style="text-align: left;"><?php _e( 'Don’t waste time on HTML or CSS. Pick one from the many <strong>ready to use elegant templates</strong> to send your next email campaign.', 'email-subscribers' ); ?></p>
            </div>
            <div class="column column_one_half last">
                <div style="text-align: center;"><i class="dashicons dashicons-es dashicons-redo"></i><h4 class="es_block_headline"><strong><?php _e( 'Customize confirmation and unsubscribe page', 'email-subscribers' ); ?></strong></h4></div>
                <p style="text-align: left;"><?php _e( 'Communicate with subscribers. Redirect them to beautifully designed <strong>confirmation and unsubscribe pages</strong> on your website.', 'email-subscribers' ); ?></p>
            </div>
        </div>
    </div>
</div>