<?php

function keypic_init()
{
	global $vbulletin;

	if (!($FormID = $vbulletin->options['keypic_formid']))
	{
		if (defined('THIS_SCRIPT'))
		{
			$vbulletin->keypic = array();
		}

		return;
	}

	$keypic_version = Keypic::getVersion();

	Keypic::setFormID($FormID);
	Keypic::setUserAgent("User-Agent: vBulletin/{$vbulletin->options['templateversion']} | Keypic/" . $keypic_version);

	if (!isset($vbulletin->keypic['status']) && $FormID)
	{
		$response = Keypic::checkFormID($FormID);
		$vbulletin->keypic['status'] = $response['status'];
		build_datastore('keypic', serialize($vbulletin->keypic), 1);
	}

	if ($vbulletin->keypic['status'] != 'response' && defined('THIS_SCRIPT'))
	{
		$vbulletin->keypic = array();
	}
}

function keypic_global_auto_edit(&$output)
{
	global $vbulletin, $keypic_post;

	if ($keypic_post)
	{
		$output = preg_replace('/(<div class=\"blockfoot actionbuttons\">(.*?)<\/div>)/s', '\\1' . $keypic_post, $output);
	}
}

function keypic_post_submit(&$dataman, &$post)
{
	global $vbulletin;

	if (isset($vbulletin->keypic['forms']['post']) && $vbulletin->keypic['forms']['post']['enabled'] == 1)
	{
		$vbulletin->input->clean_gpc('p', 'Token', TYPE_STR);

		if (strval($vbulletin->GPC['Token']) === '')
		{
			$dataman->error('invalid_keypic_post_token');
			return;
		}

		$message = $post['message'];
		$spam = Keypic::isSpam($vbulletin->GPC['Token'], $vbulletin->userinfo['email'], $vbulletin->userinfo['username'], $message, $ClientFingerprint = '');

		if(!is_numeric($spam) || $spam > Keypic::getSpamPercentage())
		{
			$dataman->set('visible', 0);
			$post['visible'] = 0;
		}

    		$keypic_details = array('token' => $vbulletin->GPC['Token'], 'ts' => TIMENOW, 'spam' => $spam);
		$dataman->set('keypic_details', serialize($keypic_details));
	}
}

function keypic_contact_submit($message, $name, $email, &$errors)
{
	global $vbulletin, $vbphrase;

	if (isset($vbulletin->keypic['forms']['contact']) && $vbulletin->keypic['forms']['contact']['enabled'] == 1)
	{
		$vbulletin->input->clean_gpc('p', 'Token', TYPE_STR);

		if (strval($vbulletin->GPC['Token']) === '')
		{
			$errors[] = fetch_error('invalid_keypic_post_token');
			return;
		}

		$spam = Keypic::isSpam($vbulletin->GPC['Token'], $email, $name, $message, $ClientFingerprint = '');

		if(!is_numeric($spam) || $spam > Keypic::getSpamPercentage())
		{
			$errors[] = fetch_error('keypic_contact_spam_message');
		}
	}
}

function keypic_register_submit(&$userdata)
{
	global $vbulletin;

	if (isset($vbulletin->keypic['forms']['register']) && $vbulletin->keypic['forms']['register']['enabled'] == 1)
	{		
		$vbulletin->input->clean_gpc('p', 'Token', TYPE_STR);
		
		if (strval($vbulletin->GPC['Token']) === '')
		{
			$userdata->error('invalid_keypic_post_token');
			return;
		}

		$email = $vbulletin->GPC['email'];
		$username = $vbulletin->GPC['username'];

		$spam = Keypic::isSpam($vbulletin->GPC['Token'], $email, $username, $ClientMessage = '', $ClientFingerprint = '');

		if(!is_numeric($spam) || $spam > Keypic::getSpamPercentage())
		{
			$userdata->error('keypic_register_spam_message');
		}
	}		
}

function keypic_login_submit(&$return_value)
{
	global $vbulletin;

	if (isset($vbulletin->keypic['forms']['login']) && $vbulletin->keypic['forms']['login']['enabled'] == 1 && $vbulletin->GPC['logintype'] != 'cplogin' && $vbulletin->GPC['logintype'] != 'modcplogin' && $vbulletin->userinfo['userid'])
	{	
		$vbulletin->input->clean_gpc('p', 'Token', TYPE_STR);
		
		if (strval($vbulletin->GPC['Token']) === '')
		{
			eval(standard_error(fetch_error('invalid_keypic_post_token')));
			return;
		}
		
		/*$vbulletin->userinfo = fetch_userinfo($vbulletin->userinfo['userid']);
		$email = $vbulletin->userinfo['email'];
		$username = $vbulletin->userinfo['username'];
		*/

		$spam = Keypic::isSpam($vbulletin->GPC['Token'], null, null, $ClientMessage = '', $ClientFingerprint = '');

		if(!is_numeric($spam) || $spam > Keypic::getSpamPercentage())
		{
			$return_value = false;
			eval(standard_error(fetch_error('keypic_login_spam_message')));
		}
	}		
}
