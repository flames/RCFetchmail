<?php
/******
 *
 *	Fetchmail Roundcube Plugin (RC0.4 and above), Backend: MySQL to Fetchmail
 *	Developped by Arthur Mayer, a.mayer@citex.net
 *	Released under GPL license (http://www.gnu.org/licenses/gpl.txt)
 *
******/

class ispcp_fetchmail extends rcube_plugin {
	public $task = 'settings';
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function init() {
		$this->load_config();
		$this->add_texts('localization/', true);
		$rcmail = rcmail::get_instance();
		$this->register_action('plugin.ispcp_fetchmail', array($this, 'init_html'));
		$this->register_action('plugin.ispcp_fetchmail.save', array($this, 'save'));
		$this->register_action('plugin.ispcp_fetchmail.del', array($this, 'del'));
		$this->register_action('plugin.ispcp_fetchmail.enable', array($this, 'enable'));
		$this->register_action('plugin.ispcp_fetchmail.disable', array($this, 'disable'));
		$this->api->output->add_handler('fetchmail_form', array($this, 'gen_form'));
		$this->api->output->add_handler('fetchmail_table', array($this, 'gen_table'));
		$this->include_script('fetchmail.js');
	}
	function load_config() {
		$rcmail = rcmail::get_instance();
		$config = "plugins/ispcp_fetchmail/config/config.inc.php";
		if (file_exists($config))
			include $config;
		else if (file_exists($config . ".dist"))
			include $config . ".dist";
		if (is_array($rcmail_config)) {
			$arr = array_merge($rcmail->config->all(), $rcmail_config);
			$rcmail->config->merge($arr);
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function init_html() {
		$rcmail = rcmail::get_instance();
		$rcmail->output->set_pagetitle($this->gettext('fetchmail'));
		$rcmail->output->send('ispcp_fetchmail.fetchmail');
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function disable() {
		$rcmail = rcmail::get_instance();
		$id = get_input_value('_id', RCUBE_INPUT_GET);
		if ($id != 0 || $id != '') {
			$sql = "UPDATE virtual_fetchmail SET active = '0' WHERE mailget_id = '$id' LIMIT 1";
			$update = $rcmail->db->query($sql);
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function enable() {
		$rcmail = rcmail::get_instance();
		$id     = get_input_value('_id', RCUBE_INPUT_GET);
		if ($id != 0 || $id != '') {
			$sql = "UPDATE virtual_fetchmail SET active = '1' WHERE mailget_id = '$id' LIMIT 1";
			$update = $rcmail->db->query($sql);
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function del() {
		$rcmail = rcmail::get_instance();
		$id = get_input_value('_id', RCUBE_INPUT_GET);
		if ($id != 0 || $id != '') {
			$sql = "DELETE FROM virtual_fetchmail WHERE mailget_id = '$id' LIMIT 1";
			$delete = $rcmail->db->query($sql);
		}
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function save() {
		$rcmail   = rcmail::get_instance();
		$id       = get_input_value('_id', RCUBE_INPUT_POST);
		$userhere = $rcmail->user->data['username'];
		$typ      = get_input_value('_fetchmailtyp', RCUBE_INPUT_POST);
		$server   = get_input_value('_fetchmailserver', RCUBE_INPUT_POST);
		$user     = get_input_value('_fetchmailuser', RCUBE_INPUT_POST);
		$pass     = get_input_value('_fetchmailpass', RCUBE_INPUT_POST);
		$delete   = get_input_value('_fetchmaildelete', RCUBE_INPUT_POST);
		$enabled  = get_input_value('_fetchmailenabled', RCUBE_INPUT_POST);
		$newentry = get_input_value('_fetchmailnewentry', RCUBE_INPUT_POST);
		if (!$delete) { $delete = 0; } else { $delete = 1; }
		if (!$enabled) { $enabled = 0; } else { $enabled = 1; }
		if ($newentry OR $id == '') {
			$sql = "SELECT * FROM virtual_fetchmail WHERE userhere='".$userhere."'";
			$result = $rcmail->db->query($sql);
			$limit = $rcmail->config->get('fetchmail_limit');
			$num_rows = $rcmail->db->num_rows($result);
			if ($num_rows < $limit) {
				$sql = "INSERT INTO virtual_fetchmail (userhere, active, options, type, remoteserver, remoteuser, remotepass) VALUES ('$userhere', '$enabled', '$delete', '$typ', '$server', '$user', '$pass')";
				$insert = $rcmail->db->query($sql);
				$rcmail->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
			} else {
				$rcmail->output->command('display_message', 'Error: '.$this->gettext('fetchmaillimitreached'), 'error');
			}
		}
		else {
			$sql = "UPDATE virtual_fetchmail SET userhere = '$userhere', active = '$enabled', options = '$delete', type = '$typ', remoteserver = '$server', remoteuser = '$user', remotepass = '$pass' WHERE mailget_id = '$id' LIMIT 1";
			$update = $rcmail->db->query($sql);
			$rcmail->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
		}
		$this->init_html();
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function gen_form() {
		$rcmail = rcmail::get_instance();
		$id = get_input_value('_id', RCUBE_INPUT_GET);
		$userhere = $rcmail->user->data['username'];
		// auslesen start
		if ($id != '' || $id != 0) {
			$sql = "SELECT * FROM virtual_fetchmail WHERE userhere='".$userhere."' AND mailget_id='".$id."'";
			$result = $rcmail->db->query($sql);
			while ($row = $rcmail->db->fetch_assoc($result)) {
				$enabled        = $row['active'];
				$delete         = $row['options'];
				$mailget_id     = $row['mailget_id'];
				$type           = $row['type'];
				$remoteserver   = $row['remoteserver'];
				$remoteuser     = $row['remoteuser'];
				$remotepass     = $row['remotepass'];
			}
		}
		$newentry = 0;
		$out .= '<fieldset><legend>'.$this->gettext('fetchmail').' - '.$userhere.'</legend>'."\n";
		$out .= '<br />' . "\n";
		$out .= '<table' . $attrib_str . ">\n\n";
		$hidden_id = new html_hiddenfield(array(
			'name' => '_id',
			'value' => $mailget_id
		));
		$out .= $hidden_id->show();
		$field_id           = 'fetchmailtyp';
		$input_fetchmailtyp = new html_select(array(
			'name' => '_fetchmailtyp',
			'id' => $field_id
		));
		$input_fetchmailtyp->add(array(
			'POP3',
			'IMAP'
		), array(
			'pop3',
			'imap'
		));
		$out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output($this->gettext('fetchmailtyp')), $input_fetchmailtyp->show($type));
		$field_id              = 'fetchmailserver';
		$input_fetchmailserver = new html_inputfield(array(
			'name' => '_fetchmailserver',
			'id' => $field_id,
			'maxlength' => 320,
			'size' => 40
		));
		$out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output($this->gettext('fetchmailserver')), $input_fetchmailserver->show($remoteserver));
		$field_id            = 'fetchmailuser';
		$input_fetchmailuser = new html_inputfield(array(
			'name' => '_fetchmailuser',
			'id' => $field_id,
			'maxlength' => 320,
			'size' => 40
		));
		$out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output($this->gettext('username')), $input_fetchmailuser->show($remoteuser));
		$field_id            = 'fetchmailpass';
		$input_fetchmailpass = new html_passwordfield(array(
			'name' => '_fetchmailpass',
			'id' => $field_id,
			'maxlength' => 320,
			'size' => 40,
			'autocomplete' => 'off'
		));
		$out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output($this->gettext('password')), $input_fetchmailpass->show($remotepass));
		$field_id              = 'fetchmaildelete';
		$input_fetchmaildelete = new html_checkbox(array(
			'name' => '_fetchmaildelete',
			'id' => $field_id,
			'value' => '1'
		));
		$out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output($this->gettext('fetchmaildelete')), $input_fetchmaildelete->show($delete));
		$field_id               = 'fetchmailenabled';
		$input_fetchmailenabled = new html_checkbox(array(
			'name' => '_fetchmailenabled',
			'id' => $field_id,
			'value' => '1'
		));
		$out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output($this->gettext('fetchmailenabled')), $input_fetchmailenabled->show($enabled));
		if ($id != '' || $id != 0) {
			$field_id                = 'fetchmailnewentry';
			$input_fetchmailnewentry = new html_checkbox(array(
				'name' => '_fetchmailnewentry',
				'id' => $field_id,
				'value' => '1'
			));
			$out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output($this->gettext('fetchmailnewentry')), $input_fetchmailnewentry->show($newentry));
		}
		$out .= "\n</table>";
		$out .= '<br />' . "\n";
		$out .= "</fieldset>\n";
		$rcmail->output->add_gui_object('fetchmailform', 'fetchmail-form');
		return $out;
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function gen_table($attrib) {
		$rcmail = rcmail::get_instance();
		$userhere = $rcmail->user->data['username'];
		$out = '<fieldset><legend>'.$this->gettext('fetchmail_entries').' - '.$userhere.'</legend>'."\n";
		$out .= '<br />' . "\n";
		$fetch_table = new html_table(array(
			'id' => 'fetch-table',
			'class' => 'records-table',
			'cellspacing' => '0',
			'cols' => 4
		));
		$fetch_table->add_header(array(
			'width' => '184px'
		), $this->gettext('fetchmailserver'));
		$fetch_table->add_header(array(
			'width' => '184px'
		), $this->gettext('username'));
		$fetch_table->add_header(array(
			'width' => '26px'
		), '');
		$fetch_table->add_header(array(
			'width' => '26px'
		), '');
		$sql = "SELECT * FROM virtual_fetchmail WHERE userhere='$userhere'";
		$result = $rcmail->db->query($sql);
		$num_rows = $rcmail->db->num_rows($result);
		while ($row = $rcmail->db->fetch_assoc($result)) {
			$class = ($class == 'odd' ? 'even' : 'odd');
			if ($row['mailget_id'] == get_input_value('_id', RCUBE_INPUT_GET)) {
				$class = 'selected';
			}
			$fetch_table->set_row_attribs(array(
				'class' => $class,
				'id' => 'fetch_' . $row['mailget_id']
			));
			$this->_fetch_row($fetch_table, $row['remoteserver'], $row['remoteuser'], $row['active'], $row['mailget_id'], $attrib);
		}
		if ($num_rows == 0) {
			$fetch_table->add(array(
				'colspan' => '4'
			), rep_specialchars_output($this->gettext('nofetch')));
			$fetch_table->set_row_attribs(array(
				'class' => 'odd'
			));
			$fetch_table->add_row();
		}
		$out .= "<div id=\"fetch-cont\">" . $fetch_table->show() . "</div>\n";
		$out .= '<br />' . "\n";
		$out .= "</fieldset>\n";
		return $out;
	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	private function _fetch_row($fetch_table, $col_remoteserver, $col_remoteuser, $active, $id, $attrib) {
		$fetch_table->add(array('onclick' => 'fetchmail_edit('.$id.');'), $col_remoteserver);
		$fetch_table->add(array('onclick' => 'fetchmail_edit('.$id.');'), $col_remoteuser);
		$disable_button = html::img(array(
			'src' => $attrib['enableicon'],
			'alt' => $this->gettext('enabled'),
			'border' => 0,
			'id' => 'img_'.$id
		));
		$enable_button = html::img(array(
			'src' => $attrib['disableicon'],
			'alt' => $this->gettext('disabled'),
			'border' => 0,
			'id' => 'img_'.$id
		));
		$del_button = html::img(array(
			'src' => $attrib['deleteicon'],
			'alt' => $this->gettext('delete'),
			'border' => 0
		));
		if ($active == 1) { $status_button = $disable_button; }
		else { $status_button = $enable_button; }
		$fetch_table->add(array('id' => 'td_'.$id, 'onclick' => 'row_edit('.$id.','.$active.');'), $status_button);
		$fetch_table->add(array('id' => 'td_'.$id, 'onclick' => 'row_del('.$id.');'), $del_button);
		return $fetch_table;
	}
}

?>
