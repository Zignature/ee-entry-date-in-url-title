<?php

class Entry_id_in_url_title_ext {
    var $settings = array();
    var $name = 'Entry ID in URL Title';
    var $version = '1.0';
    var $description = 'On publish adds the ID to the end of the url title.';
    var $settings_exist = 'y';
    var $docs_url = '';

    function __construct($settings = '') {
        $this->EE =& get_instance();
        $this->settings = $settings;
    }

    function insert_entry_id_ee2($entry_id, $meta,  $values) {
        if (array_key_exists('entry_id', $values)
            && $values['entry_id'] === '0'
            && array_key_exists('url_title', $meta)
            && array_key_exists('channel_id', $values)
            && !empty($this->settings)
            && array_search($values['channel_id'], $this->settings['chosen_channels']) !== FALSE) {

            $new_url_title = $this->format_url_title($values['channel_id'], $entry_id, $meta['url_title']);

            if ($new_url_title) {
                $_POST['url_title'] = $new_url_title;
                $this->save_url_title($new_url_title, $entry_id);
            }
        }
    }

    function insert_entry_id_ee3($entry, $values) {
        if (array_key_exists('channel_id', $values)
            && array_key_exists('url_title', $values)
            && array_key_exists('entry_id', $values)
            && !empty($this->settings)
            && array_search($values['channel_id'], $this->settings['chosen_channels']) !== FALSE) {

            $new_url_title = $this->format_url_title($values['channel_id'], $values['entry_id'], $values['url_title']);

            if ($new_url_title) $this->save_url_title($new_url_title, $values['entry_id']);
        }
    }

    private function format_url_title($cid, $eid, $ut) {
        if (!empty($cid) && !empty($eid) && !empty($ut)) {
            if ($this->settings['position_of_id'] == 'front') {
                $new_url_title = $eid . '-' . $ut;
            }
            else {
                $new_url_title = $ut . '-' . $eid;
            }

            return $new_url_title;
        }

        return false;
    }

    private function save_url_title($new_url_title, $entry_id) {
        $this->EE->db->update(
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

        $query = $this->EE->db->get('channels');

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

        $settings['position_of_id'] = array(
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

        $method = (version_compare(APP_VER, '3.0', '>=')) ? 'insert_entry_id_ee3' : 'insert_entry_id_ee2';
        $hook = (version_compare(APP_VER, '3.0', '>=')) ? 'after_channel_entry_insert' : 'entry_submission_absolute_end';

        $data = array(
            'class'     => __CLASS__,
            'method'    => $method,
            'hook'      => $hook,
            'settings'  => '',
            'priority'  => 1,
            'version'   => $this->version,
            'enabled'   => 'y'
        );

        $this->EE->db->insert('extensions', $data);
    }

    function disable_extension() {
        $this->EE->db->where('class', __CLASS__);
        $this->EE->db->delete('extensions');
    }
}