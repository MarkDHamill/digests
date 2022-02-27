<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2020 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests\migrations;

class release_3_3_13 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_3_12',
		);
	}

	public function update_data()
	{

		// The config variable users per page changes to rows per page. Let's first grab the current value.
		$rows_per_page = (int) $this->config->offsetGet('phpbbservices_digests_users_per_page');
		if ($rows_per_page === 0)
		{
			$rows_per_page = 20;
		}

		return array(
			// Set the value of users per page to the value of rows per page
			array('if', array(
				($this->config['phpbbservices_digests_users_per_page']),
				array('config.remove', array('phpbbservices_digests_users_per_page', $rows_per_page)),
			)),

			// Add some new configuration variables related to reporting
			array('config.add', array('phpbbservices_digests_clear_report', '0')),
			array('config.add', array('phpbbservices_digests_reporting_days', '30')),
			array('config.add', array('phpbbservices_digests_reporting_enable', '1')),
			array('config.add', array('phpbbservices_digests_rows_per_page', $rows_per_page)),

			// Add the reports ACP modules
			array('module.add', array(
				'acp',
				'ACP_CAT_DIGESTS',
				array(
					'module_basename' => '\phpbbservices\digests\acp\main_module',
					'modes'           => array('digests_report'),
				),
			),
		));
	}

	public function update_schema()
	{

		return array(
			'add_tables' => array(
				// Add digests reports table
				$this->table_prefix . 'digests_report'	=> array(
					'COLUMNS' => array(
						'digests_report_id' => array('ULINT', null, 'auto_increment'),
						'date_hour_sent_utc' => array('UINT:11', 0),
						'started' => array('UINT:11', 0),
						'ended' => array('UINT:11', 0),
						'mailed' => array('USINT', 0),
						'skipped' => array('USINT', 0),
						'execution_time_secs' => array('DECIMAL:7', 0),
						'memory_used_mb' => array('DECIMAL:13', 0),
						'cron_type' => array('TINT:1', 0),
					),
					'PRIMARY_KEY' => array('digests_report_id'),
					'KEYS' => array(
						'date_hour_sent' => ['UNIQUE','date_hour_sent_utc'],
						'started' => ['INDEX','started'],
						'ended' => ['INDEX','ended'],
						'execution_time' => ['INDEX','execution_time_secs'],
						'memory_used' => ['INDEX','memory_used_mb'],
					),
				),
				// Add digests report details table
				$this->table_prefix . 'digests_report_details'	=> array(
					'COLUMNS' => array(
						'digests_report_id' => array('UINT', 0),
						'user_id' => array('UINT:10', 0),
						'digest_type' => array('VCHAR:4', 'NONE'),
						'posts_in_digest' => array('USINT', 0),
						'msgs_in_digest' => array('USINT', 0),
						'creation_time' => array('UINT:11', 0),
						'status' => array('TINT:1', 0),
						'sent' => array('TINT:1', 0),
					),
					'KEYS' => array(
						'report_id_user_id' => ['UNIQUE','digests_report_id,user_id'],
						'user_id' => ['INDEX','user_id'],
						'creation_time' => ['INDEX','creation_time'],
					),
				),
			),
		);

	}

	public function revert_schema()
	{
		return array(
			'drop_tables'    => array(
				$this->table_prefix . 'digests_report',
				$this->table_prefix . 'digests_report_details',
			),
		);
	}

}