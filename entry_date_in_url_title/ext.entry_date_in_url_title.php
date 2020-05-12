<?php

class Entry_date_in_url_title_ext {
    var $settings = array();
    var $name = 'Entry Date in URL Title';
    var $version = '1.0';
    var $description = 'On publish prepends or appends the entry date (yyyy-mm-dd) to the url title. Forked from https://github.com/mattnap/entry-id-in-url-title-ee';
    var $settings_exist = 'y';
    var $docs_url = 'https://github.com/Zignature/ee-entry-date-in-url-title/';

    function __construct($settings = '') {
        $this->settings = $settings;
    }

    function insert_entry_date($entry, $values) {
        if (array_key_exists('channel_id', $values)
            && array_key_exists('entry_id', $values)
            && array_key_exists('url_title', $values)
            && array_key_exists('year', $values)
            && array_key_exists('month', $values)
            && array_key_exists('day', $values)
            && !empty($this->settings)
            && array_search($values['channel_id'], $this->settings['chosen_channels']) !== FALSE) {

            $new_url_title = $this->format_url_title($values['channel_id'], $values['year'], $values['month'], $values['day'], $values['url_title']);

            if ($new_url_title) $this->save_url_title($new_url_title, $values['entry_id']);
        }
    }

    private function format_url_title($cid, $edy, $edm, $edd, $ut) {
        if (!empty($cid) && !empty($edy) && !empty($edm) && !empty($edd) && !empty($ut)) {
            if ($this->settings['position_of_date'] == 'front') {
                $new_url_title = $edy . '-' . $edm . '-' . $edd . '--' . $ut;
            }
            else {
                $new_url_title = $ut . '--' . $edy . '-' . $edm . '-' . $edd;
            }

            return $new_url_title;
        }

        return false;
    }

    private function save_url_title($new_url_title, $entry_id) {
        ee()->db->update(
            'channel_titles',
            array(
                'url_title'  => $new_url_title
            ),
            array(
                'entry_id' => $entry_id
            )
        );
    }

    function settings() {
        $channels = array();

        $query = ee()->db->get('channels');

        if ($query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $channels[$row->channel_id] = $row->channel_title;
            }
        }

        $settings = array();

        $settings['chosen_channels'] = array(
            'ms',
            $channels,
            ''
        );

        $settings['position_of_date'] = array(
            'r',
            array(
                'front' => 'prepend',
                'back' => 'append'
            ),
            'back'
        );

        return $settings;
    }

    function activate_extension() {
        $this->disable_extension();

        $method = 'insert_entry_date';
        $hook   = 'after_channel_entry_insert';

        $data = array(
            'class'     => __CLASS__,
            'method'    => $method,
            'hook'      => $hook,
            'settings'  => '',
            'priority'  => 1,
            'version'   => $this->version,
            'enabled'   => 'y'
        );

        ee()->db->insert('extensions', $data);
    }

    function disable_extension() {
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('extensions');
    }
}
