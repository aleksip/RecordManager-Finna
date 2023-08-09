<?php

/**
 * Aipa record class
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022-2023.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */

namespace RecordManager\Finna\Record;

use RecordManager\Base\Database\DatabaseInterface as Database;
use RecordManager\Base\Http\ClientManager as HttpClientManager;
use RecordManager\Base\Record\CreateRecordTrait;
use RecordManager\Base\Record\PluginManager as RecordPluginManager;
use RecordManager\Base\Record\Qdc;
use RecordManager\Base\Utils\Logger;
use RecordManager\Base\Utils\MetadataUtils;

/**
 * Aipa record class
 *
 * @category DataManagement
 * @package  RecordManager
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/NatLibFi/RecordManager
 */
class Aipa extends Qdc
{
    use CreateRecordTrait;

    /**
     * Record plugin manager
     *
     * @var RecordPluginManager
     */
    protected $recordPluginManager;

    /**
     * Constructor
     *
     * @param array               $config              Main configuration
     * @param array               $dataSourceConfig    Data source settings
     * @param Logger              $logger              Logger
     * @param MetadataUtils       $metadataUtils       Metadata utilities
     * @param HttpClientManager   $httpManager         HTTP client manager
     * @param ?Database           $db                  Database
     * @param RecordPluginManager $recordPluginManager Record plugin manager
     */
    public function __construct(
        $config,
        $dataSourceConfig,
        Logger $logger,
        MetadataUtils $metadataUtils,
        HttpClientManager $httpManager,
        ?Database $db,
        RecordPluginManager $recordPluginManager
    ) {
        parent::__construct(
            $config,
            $dataSourceConfig,
            $logger,
            $metadataUtils,
            $httpManager,
            $db
        );
        $this->recordPluginManager = $recordPluginManager;
    }

    /**
     * Return fields to be indexed in Solr
     *
     * @param Database $db Database connection. Omit to avoid database lookups for
     *                     related records.
     *
     * @return array<string, mixed>
     */
    public function toSolrArray(Database $db = null)
    {
        $data = parent::toSolrArray($db);
        $data['record_format'] = 'aipa';

        // Fields to merge from encapsulated records.
        $mergeFields = [
            'lrmi' => [
                'educational_audience_str_mv',
                'educational_level_str_mv',
                'educational_aim_str_mv',
                'educational_subject_str_mv',
                'educational_material_type_str_mv',
                'topic_id_str_mv',
            ],
        ];

        // Merge fields from encapsulated records.
        foreach ($this->doc->item as $item) {
            $format = strtolower((string)$item->format);
            if (empty($mergeFields[$format])) {
                continue;
            }
            $lrmi = $this->createRecord($format, $item->asXML(), (string)$item->id, 'aipa');
            $lrmiFields = $lrmi->toSolrArray($db);
            foreach ($mergeFields[$format] as $mergeField) {
                $data[$mergeField] = array_unique(array_merge(
                    $data[$mergeField] ?? [],
                    $lrmiFields[$mergeField] ?? []
                ));
            }
        }

        return $data;
    }

    /**
     * Return format from predefined values
     *
     * @return string
     */
    public function getFormat()
    {
        return 'AIPA';
    }

    /**
     * Return URLs associated with object
     *
     * @return array
     */
    protected function getUrls()
    {
        return [];
    }
}
