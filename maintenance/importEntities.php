<?php

namespace Wikibase\Import\Maintenance;

use MediaWiki\Logger\LoggerFactory;
use Wikibase\Import\EntityImporter;
use Wikibase\Import\EntityImporterFactory;
use Wikibase\Import\PropertyIdLister;
use Wikibase\Repo\WikibaseRepo;

$IP = getenv( 'MW_INSTALL_PATH' );
if( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once( "$IP/maintenance/Maintenance.php" );

class ImportEntities extends \Maintenance {

	private $entity;

	private $file;

	private $allProperties;

	private $apiUrl;

	public function __construct() {
		parent::__construct();

		$this->addOptions();
	}

	private function addOptions() {
		$this->addOption( 'file', 'File with list of entity ids to import', false, true );
		$this->addOption( 'entity', 'ID of entity to import', false, true );
		$this->addOption( 'all-properties', 'Import all properties', false, true );
	}

	public function execute() {
		if ( $this->extractOptions() === false ) {
			$this->maybeHelp( true );

			return;
		}

		$logger = LoggerFactory::getInstance( 'console' );

		$entityImporterFactory = new EntityImporterFactory( $this->getConfig(), $logger );
		$entityImporter = $entityImporterFactory->newEntityImporter();

		if ( $this->allProperties ) {
			$propertyLister = new PropertyLister();
			$ids = $propertyIdLister->fetch( $this->apiUrl );

			$entityImporter->importIds( $ids );
		}

		if ( $this->file ) {
			$rows = file( $this->file );

			if ( !is_array( $rows ) ) {
				$this->logger->error( 'File is invalid.' );
			}

			$ids = array_map( 'trim', $rows );
			$entityImporter->importIds( $ids );
		}

		if ( $this->entity ) {
			$idParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();

			try {
				$id = $idParser->parse( $this->entity );

				$entityImporter->importIds( array( $id->getSerialization() ) );
			} catch ( \Exception $ex ) {
				$logger->error( 'Invalid entity ID' );
			}
		}

		$logger->info( 'Done' );
	}

	private function extractOptions() {
		$this->entity = $this->getOption( 'entity' );
		$this->file = $this->getOption( 'file' );
		$this->allProperties = $this->getOption( 'all-properties' );

		if ( $this->file === null && $this->allProperties === null && $this->entity === null ) {
			return false;
		}

		return true;
	}

}

$maintClass = "Wikibase\Import\Maintenance\ImportEntities";
require_once RUN_MAINTENANCE_IF_MAIN;
