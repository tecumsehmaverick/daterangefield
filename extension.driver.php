<?php
	
	class Extension_DateRangeField extends Extension {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function about() {
			return array(
				'name'			=> 'Field: Date Range',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-02-17',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				),
				'description'	=> 'A wacky date range field.'
			);
		}
		
		public function uninstall() {
			return Symphony::Database()->query("DROP TABLE `tbl_fields_bilink`");
		}
		
		public function install() {
			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_daterange` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`field_id` INT(11) UNSIGNED NOT NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
		}
		
		/*
		public function update($previousVersion) {
			if (version_compare($previousVersion, '1.0.14', '<')) {
				Symphony::Database()->query("
					ALTER TABLE `tbl_fields_bilink`
					ADD COLUMN `allow_editing` ENUM('yes','no') DEFAULT 'no';
				");
			}
			
			return true;
		}
		*/
		
	/*-------------------------------------------------------------------------
		Utilites:
	-------------------------------------------------------------------------*/
		
		protected $addedPublishHeaders = false;
		
		public function addPublishHeaders($page) {
			if (!$this->addedPublishHeaders) {
				$page->addStylesheetToHead(URL . '/extensions/daterangefield/assets/publish.css', 'screen', 32121277);
				$page->addScriptToHead(URL . '/extensions/daterangefield/assets/publish.js', 32121278);
				
				$this->addedPublishHeaders = true;
			}
		}
	}
		
?>