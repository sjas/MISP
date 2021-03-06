<?php
App::uses('AppShell', 'Console/Command');
class AdminShell extends AppShell
{
	public $uses = array('Event', 'Post', 'Attribute', 'Job', 'User', 'Task', 'Whitelist', 'Server', 'Organisation', 'AdminSetting');

	public function jobGenerateCorrelation() {
		$jobId = $this->args[0];
		$this->loadModel('Job');
		$this->Job->id = $jobId;
		$this->loadModel('Attribute');
		$this->Attribute->generateCorrelation($jobId, 0);
		$this->Job->saveField('progress', 100);
		$this->Job->saveField('message', 'Job done.');
		$this->Job->saveField('status', 4);
	}

	public function jobPurgeCorrelation() {
		$jobId = $this->args[0];
		$this->loadModel('Job');
		$this->Job->id = $jobId;
		$this->loadModel('Attribute');
		$this->Attribute->purgeCorrelations();
		$this->Job->saveField('progress', 100);
		$this->Job->saveField('message', 'Job done.');
		$this->Job->saveField('status', 4);
	}

	public function jobGenerateShadowAttributeCorrelation() {
		$jobId = $this->args[0];
		$this->loadModel('Job');
		$this->Job->id = $jobId;
		$this->loadModel('ShadowAttribute');
		$this->ShadowAttribute->generateCorrelation($jobId);
	}

	public function jobUpgrade24() {
		$jobId = $this->args[0];
		$user_id = $this->args[1];
		$this->loadModel('Job');
		$this->Job->id = $jobId;
		$this->loadModel('Server');
		$this->Server->upgrade2324($user_id, $jobId);
		$this->Job->saveField('progress', 100);
		$this->Job->saveField('message', 'Job done.');
		$this->Job->saveField('status', 4);
	}

	public function prune_update_logs() {
		$jobId = $this->args[0];
		$user_id = $this->args[1];
		$user = $this->User->getAuthUser($user_id);
		$this->loadModel('Job');
		$this->Job->id = $jobId;
		$this->loadModel('Log');
		$this->Log->pruneUpdateLogs($jobId, $user);
		$this->Job->saveField('progress', 100);
		$this->Job->saveField('message', 'Job done.');
		$this->Job->saveField('status', 4);
	}

	public function getSetting() {
		$param = empty($this->args[0]) ? 'all' : $this->args[0];
		$settings = $this->Server->serverSettingsRead();
		$result = $settings;
		if (!empty($param)) {
			$result = 'No valid setting found for ' . $param;
			foreach ($settings as $setting) {
				if ($setting['setting'] == $param) {
					$result = $setting;
					break;
				}
			}
		}
		echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
	}

	public function setSetting() {
		$setting = !isset($this->args[0]) ? null : $this->args[0];
		$value = !isset($this->args[1]) ? null : $this->args[1];
		if (empty($setting) || $value === null) {
			echo 'Invalid parameters. Usage: ' . APP . 'Console/cake Admin setSetting [setting_name] [setting_value]';
		} else {
			$this->Server->serverSettingsSaveValue($setting, $value);
		}
	}

	public function setDatabaseVersion() {
		if (empty($this->args[0])) echo 'Invalid parameters. Usage: ' . APP . 'Console/cake Admin setDatabaseVersion [db_version]' . PHP_EOL;
		else {
			$db_version = $this->AdminSetting->find('first', array(
				'conditions' => array('setting' => 'db_version')
			));
			if (!empty($db_version)) {
				$db_version['value'] = trim($this->args[0]);
				$this->AdminSetting->save($db_version);
				echo 'Database version set. MISP will replay all of the upgrade scripts since the selected version on the next user login.' . PHP_EOL;
			} else {
				echo 'Something went wrong. Could not find the existing db version.' . PHP_EOL;
			}
		}
	}

}
