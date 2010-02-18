<?php
	
	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	class FieldDateRange extends Field {
		protected $_driver = null;
		
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function __construct(&$parent) {
			parent::__construct($parent);
			
			$this->_name = 'Date Range';
			$this->_required = true;
			$this->_driver = $this->_engine->ExtensionManager->create('daterangefield');
			
			// Set defaults:
			$this->set('show_column', 'yes');
			$this->set('size', 'medium');
			$this->set('required', 'yes');
		}
		
		public function createTable() {
			$field_id = $this->get('id');
			
			return $this->_engine->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_{$field_id}` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`entry_id` INT(11) UNSIGNED NOT NULL,
					`mode` ENUM('entire-day', 'entire-week', 'entire-month', 'entire-year', 'until-date') DEFAULT 'entire-day',
					`from_value` TEXT DEFAULT NULL,
					`from_date` INT(11) UNSIGNED DEFAULT NULL,
					`to_value` TEXT DEFAULT NULL,
					`to_date` INT(11) UNSIGNED DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `entry_id` (`entry_id`),
					KEY `from_date` (`from_date`),
					KEY `to_date` (`to_date`)
				)
			");
		}
		
		public function canFilter() {
			return true;
		}
		
	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/
		
		public function displaySettingsPanel(&$wrapper, $errors = null, $append_before = null, $append_after = null) {
			parent::displaySettingsPanel($wrapper, $errors);
			
			$this->appendShowColumnCheckbox($wrapper);
		}
		
	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/
		
		public function displayPublishPanel(&$wrapper, $data = null, $error = null, $prefix = null, $postfix = null) {
			$this->_driver->addPublishHeaders($this->_engine->Page);
			
			$sortorder = $this->get('sortorder');
			$element_name = $this->get('element_name');
			
			$container = new XMLElement('div', $this->get('label'));
			$container->setAttribute('class', 'label');
			$container->appendChild(new XMLElement('i', __('Optional')));
			
			$group = new XMLElement('div');
			
			// From:
			$label = Widget::Label(__('Date'));
			$from = Widget::Input(
				"fields{$prefix}[$element_name]{$postfix}[from]",
				General::sanitize($data['from_value'])
			);
			$label->appendChild($from);
			$group->appendChild($label);
			
			// To:
			$label = Widget::Label(__('Until Date'));
			$from = Widget::Input(
				"fields{$prefix}[$element_name]{$postfix}[to]",
				General::sanitize($data['to_value'])
			);
			$label->appendChild($from);
			$group->appendChild($label);
			
			// Mode:
			$options = array(
				array('entire-day', false, 'Entire Day'),
				array('entire-week', false, 'Entire Week'),
				array('entire-month', false, 'Entire Month'),
				array('entire-year', false, 'Entire Year'),
				array('until-date', false, 'Until Date')
			);
			
			foreach ($options as &$option) {
				if ($option[0] != $data['mode']) continue;
				
				$option[1] = true; break;
			}
			
			$label = Widget::Label(__('Mode'));
			$from = Widget::Select(
				"fields{$prefix}[$element_name]{$postfix}[mode]",
				$options
			);
			$label->appendChild($from);
			$group->appendChild($label);
			
			$container->appendChild($group);
			$wrapper->appendChild($container);
		}
		
	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/
		
		public function checkPostFieldData($data, &$message, $entry_id = null) {
			if (!isset($data['from']) or trim($data['from']) == '') {
				$message = __(
					"Please enter a date.", array(
						$this->get('label')
					)
				);
				
				return self::__MISSING_FIELDS__;
			}
			
			return self::__OK__;
		}
		
		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null) {
			$status = self::__OK__;
			$range = array(
				'from'	=> 0,
				'to'	=> 0
			);
			
			if ($data['mode'] == 'entire-day') {
				$range['from'] = strtotime(date('Y-m-d', strtotime($data['from'])));
				$range['to'] = strtotime('+1 day', $range['from']) - 1;
			}
			
			else if ($data['mode'] == 'entire-week') {
				$from = strtotime($data['from']);
				
				while (($day_of_week = date('N', $from)) != '1') {
					$from = strtotime('-1 day', $from);
				}
				
				$range['from'] = $from;
				$range['to'] = strtotime('+7 days', $from) - 1;
			}
			
			else if ($data['mode'] == 'entire-month') {
				$range['from'] = strtotime(date('Y-m-01', strtotime($data['from'])));
				$range['to'] = strtotime('+1 month', $range['from']) - 1;
			}
			
			else if ($data['mode'] == 'entire-year') {
				$range['from'] = strtotime(date('Y-01-01', strtotime($data['from'])));
				$range['to'] = strtotime('+1 year', $range['from']) - 1;
			}
			
			else if ($data['mode'] == 'until-date') {
				$range['from'] = strtotime($data['from']);
				$range['to'] = strtotime($data['to']);
			}
			
			$result = array(
				'mode'			=> $data['mode'],
				'from_value'	=> $data['from'],
				'from_date'		=> $range['from'],
				'to_value'		=> $data['to'],
				'to_date'		=> $range['to']
			);
			
			return $result;
		}
		
	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/
		
		public function appendFormattedElement(&$wrapper, $data, $encode = false, $mode = null) {
			$element = new XMLElement($this->get('element_name'));
			
			if (!empty($data) and strlen(trim($data['from_value'])) != 0) {
				$value = General::sanitize(__(
					'%s until %s', array(
						date(__SYM_DATE_FORMAT__, $data['from_date']),
						date(__SYM_DATE_FORMAT__, $data['to_date'])
					)
				));
				
				if ($encode) $value = General::sanitize($value);
				
				$element->setAttribute('mode', $data['mode']);
				$element->setAttribute('value', $value);
				
				$date = General::createXMLDateObject($data['from_date'], 'from-date');
				$date->setAttribute('value', $data['from_value']);
				$element->appendChild($date);
				
				$date = General::createXMLDateObject($data['to_date'], 'to-date');
				$date->setAttribute('value', $data['to_value']);
				$element->appendChild($date);
			}
			
			$wrapper->appendChild($element);
		}
		
		public function prepareTableValue($data, XMLElement $link = null) {
			header('content-type: text/html');
			
			if (empty($data) or strlen(trim($data['from_value'])) == 0) return;
			
			$value = __(
				'%s until %s', array(
					date(__SYM_DATE_FORMAT__, $data['from_date']),
					date(__SYM_DATE_FORMAT__, $data['to_date'])
				)
			);
			
			if ($link) {
				$link->setValue($value);
				
				return $link->generate();
			}
			
			return $value;
		}
		
	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/
		
		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');
			
			if (!is_array($data)) $data = array($data);
			
			$this->_key++;
			$table = "t{$field_id}_{$this->_key}";
			$joins .= "LEFT JOIN\n\t\t\t\t\t`tbl_entries_data_{$field_id}` AS {$table} ON (e.id = {$table}.entry_id)";
			$where .= "\n\t\t\t\tAND (";
			
			foreach ($data as $index => $value) {
				$mode = ($andOperation ? 'AND ' : 'OR ');
				$mode = ($index == 0 ? '' : $mode);
				
				$where .= sprintf(
					'%s%s( %3$s.from_date <= %4$d AND %3$s.to_date >= %4$d )',
					"\n\t\t\t\t\t", $mode, $table, strtotime($value)
				);
			}
			
			$where .= "\n\t\t\t\t)";
			
			return true;
		}
	}
	
?>