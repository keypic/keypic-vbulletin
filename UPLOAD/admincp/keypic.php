<?php
// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('CVS_REVISION', '$RCSfile$ - $Revision: 76725 $');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

require_once(DIR . '/includes/adminfunctions_misc.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminsettings'))
{
	print_cp_no_permission();
}

// ############################# LOG ACTION ###############################
log_admin_action();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if ($_POST['do'] == 'doupdate')
{
	print_cp_header($vbphrase['keypic_form_details']);

	$vbulletin->input->clean_array_gpc('p', array(
		'enabled' => TYPE_UINT,
		'width_height'	=> TYPE_STR,
		'requestType'	=> TYPE_STR,
		'type'	=> TYPE_STR
	));

	if (!empty($vbulletin->GPC['type']))
	{
		$type = $vbulletin->GPC['type'];
		$vbulletin->keypic['forms'][$type] = array(
			'enabled'	=> $vbulletin->GPC['enabled'],
			'width_height'	=> $vbulletin->GPC['width_height'],
			'requestType'	=> $vbulletin->GPC['requestType']
		);

		build_datastore('keypic', serialize($vbulletin->keypic), 1);
	}

	define('CP_REDIRECT', 'keypic.php?do=details');
	print_stop_message('form_details_updated_successfully');
}

if ($_REQUEST['do'] == 'details')
{
	print_cp_header($vbphrase['keypic_form_details']);

	keypic_init();

	$forms = array(
		'register'	=> array(
			'formName' => 'registerform',
			'header'	=> $vbphrase['registration_form_details'],
			'enabled'	=> 1,
			'width_height'	=> '728x90',
			'requestType'	=> 'getScript'
		),

		'login'		=> array(
			'formName' => 'loginform',
			'header'	=> $vbphrase['login_form_details'],
			'enabled'	=> 0,
			'width_height'	=> '468x60',
			'requestType'	=> 'getScript'
		),

		'post'		=> array(
			'formName' => 'postform',
			'header'	=> $vbphrase['post_form_details'],
			'enabled'	=> 1,
			'width_height'	=> '728x90',
			'requestType'	=> 'getScript'
		),

		'contact'		=> array(
			'formName' => 'contactform',
			'header'	=> $vbphrase['contact_form_details'],
			'enabled'	=> 1,
			'width_height'	=> '728x90',
			'requestType'	=> 'getScript'
		),
	);

	foreach($forms AS $formKey => $form)
	{
		if (isset($vbulletin->keypic['forms'][$formKey]))
		{
			$form = array_merge($form, $vbulletin->keypic['forms'][$formKey]);
		}

		?>
		<script type=$vbulletin->"text/javascript">
		<!--
		function submitForm(formId)
		{
			document.getElementById(formId).submit();
			return true;
		}
		//-->
		</script>
		<?php

		print_form_header('keypic', 'doupdate', 1, 1, $form['formName'], '90%', '', true);
		print_table_header($form['header']);

		print_yes_no_row($vbphrase['enabled_'], 'enabled', $form['enabled'], "submitForm('{$form['formName']}');");

		if ($form['enabled'] == 1)
		{
			print_select_row($vbphrase['width_height'], 'width_height" onchange="this.form.submit();', keypic_get_width_height_options(), $form['width_height']);
			print_select_row($vbphrase['requestType'], 'requestType', keypic_get_select_requesttype(), $form['requestType']);

			if ($vbulletin->keypic['status'] == 'response')
			{
				//$contentPreview = Keypic::getIt($form['requestType'], $form['width_height']);
			}
			elseif($vbulletin->keypic['status'] == 'error')
			{
				$contentPreview = 'Keypic FormID is NOT valid.';
			}
			else
			{
				$contentPreview = 'There was some problem in fetching the content preview.';
			}

			print_label_row($vbphrase['content_preview'], $contentPreview);
		}

		construct_hidden_code('type', $formKey);
		print_submit_row($vbphrase['update'], 0);
	}
}

function keypic_get_width_height_options()
{
	$options = array(
		'250x250' => 'Square Pop-Up (250 x 250)',
		'300x250' => 'Medium Rectangle (300 x 250)',
		'336x280' => 'Large rectangle (336 x 280)',
	//	'240x400' => 'Vertical Rectangle (240 x 400)',
	//	'180x150' => 'Rectangle (180 x 150)',
	//	'300x100' => '3:1 Rectangle (300 x 100)',
		'720x300' => 'Pop-under (720 x 300)',
	//	'392x72' => 'Banner w/Naw Bar (392 x 72)',
		'468x60' => 'Full Banner (468 x 60)',
		'234x60' => 'Half Banner (234 x 60)',
	//	'80x15' => 'Micro Button (80 x 15)',
	//	'88x31' => 'Micro Bar (88 x 31)',
	//	'120x90' => 'Button 1 (120 x 90)',
	//	'120x60' => 'Button 2 (120 x 60)',
	//	'120x240' => 'Vertical Banner (120 x 240)',
		'125x125' => 'Square Button (125 x 125)',
		'728x90' => 'Leaderboard (728 x 90)',
		'120x600' => 'Skyscraper (120 x 600)',
		'160x600' => 'Wide Skyscraper (160 x 600)',
		'300x600' => 'Half Page Ad (300 x 600)'
	);

	return $options;
}

function keypic_get_select_requesttype()
{
	$options = array(
		'getScript' => 'getScript',
		//'getImage' => 'getImage'
	);

	return $options;
}

print_cp_footer();
