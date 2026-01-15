<?php
/**
 * Plugin Name: FileMaker Integrations for Movement Research
 * Description: Endpoints for sending and receiving data from Filemaker
 * Version: 1.0.0
 * Author: Chad Rossouw for HdK
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('FMMR_DIR',__DIR__.'/');
define('FMMR_FILE',__FILE__);

require FMMR_DIR.'inc/class-fmmr-init.php';
require FMMR_DIR.'inc/class-fmmr-event.php';
require FMMR_DIR.'inc/class-fmmr-location.php';
require FMMR_DIR.'inc/class-fmmr-person.php';
require FMMR_DIR.'inc/class-fmmr-fee.php';
require FMMR_DIR.'inc/class-fmmr-dates.php';
require FMMR_DIR.'inc/class-fmmr-orders.php';
require FMMR_DIR.'inc/class-fmmr-check-ins.php';

$fmmr = new FMMR_init();