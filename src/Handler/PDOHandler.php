<?php
declare(strict_types=1);

namespace MonologExtensions\Handler;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class PDOHandler extends AbstractProcessingHandler
{
    /**
	 * PDO connection
	 * @var \PDO
	 */
    private \PDO $pdo;
	
	/**
     * Table name
     *
     * @var string
     */
    protected string $tableName;
	
	/**
     * Relates database columns names to log data field keys.
     *
     * @var null|array
     */
    protected ?array $columnMap;

	
	/**
     * date format
     *
     * @var string
     */
    protected string $dateFormat;
	
		
	/**
	 * Constructor
	 * @param \PDO $pdo PDO connection
	 * @param string $tableName The log database table
	 * @param string $dateFormat The format of the timestamp: one supported by DateTime::format - must be compatible with database datetime type
	 * @param array|null $columnMap associative array to map record key to database columns
	 * @param int|string|Level $level
	 * @param bool $bubble
	 */
    public function __construct(\PDO $pdo, string $tableName,string $dateFormat = "Y-m-d H:i:s.v",?array $columnMap = null, int|string|Level $level = Logger::DEBUG, bool $bubble = true)
    {
        $this->pdo = $pdo;
		
		$this->tableName = $tableName;
		
		$this->dateFormat=$dateFormat;
		
		$this->columnMap=$columnMap;
		
        parent::__construct($level, $bubble);
		
    }

    protected function write(array $record): void
    {
		
		if ( isset($record['datetime'])){
			$record['datetime']=$record['datetime']->format($this->dateFormat);
		}
		
		// Transform the record array into columns for database insert
        if (null === $this->columnMap) {
            $dataToInsert = $this->eventIntoColumn($record);
        } else {
            $dataToInsert = $this->mapEventIntoColumn($record, $this->columnMap);
        }
		
		$fields=[];
		$values=[];
		
		foreach ($dataToInsert as $fieldName=>$value){
			$fields[]=$fieldName;
			$values[$fieldName]=':' . $fieldName;
		}
				
		$sql= sprintf("INSERT INTO %s (%s) values(%s)"
				,$this->tableName
				,implode(",",$fields)
				,implode(",",$values)
		);
		
		$this->pdo->prepare($sql)->execute($dataToInsert);

    }
	
	
	/**
     * Map event into column using the $columnMap array
     *
     * @param  array $event
     * @param  array $columnMap
     * @return array
     */
    protected function mapEventIntoColumn(array $event, ?array $columnMap = null)
    {
        if (empty($event)) {
            return [];
        }

        $data = [];
        foreach ($event as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $subvalue) {
                    if (isset($columnMap[$name][$key])) {
                        if (is_scalar($subvalue)) {
                            $data[$columnMap[$name][$key]] = $subvalue;
                            continue;
                        }

                        $data[$columnMap[$name][$key]] = var_export($subvalue, true);
                    }
                }
            } elseif (isset($columnMap[$name])) {
                $data[$columnMap[$name]] = $value;
            }
        }
        return $data;
    }

    /**
     * Transform event into column for the db table
     *
     * @param  array $event
     * @return array
     */
    protected function eventIntoColumn(array $event)
    {
        if (empty($event)) {
            return [];
        }

        $data = [];
        foreach ($event as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $subvalue) {
                    if (is_scalar($subvalue)) {
                        $data[$key] = $subvalue;
                        continue;
                    }

                    $data[$key] = var_export($subvalue, true);
                }
            } else {
                $data[$name] = $value;
            }
        }
        return $data;
    }

    
}