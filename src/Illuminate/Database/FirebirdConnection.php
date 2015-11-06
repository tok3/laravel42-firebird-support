<?php namespace Illuminate\Database;

use Closure;
use Illuminate\Support\Facades\Log;

class FireBirdConnection extends Connection {

    private $sqlScript;
    public $pdo;

    /**
     * @param $sql
     * @param null $outputParams
     * @param null $localParams
     */
    public function execThroughExecuteBlock ($sql, $outputParams=null, $localParams=null){
       if (!is_null($outputParams)){
           $outputParams = " returns ($outputParams) ";
       } else {
           $outputParams="";
       }

       if (is_null($localParams)){
           $localParams = "";
       }

       $this->sqlScript = " execute block $outputParams as $localParams begin $sql end; ";

       $this->transaction(function() {
           return $this->pdo->exec($this->sqlScript);
       });
   }

    /**
     * @param callable $callback
     * @return mixed
     * @throws \Exception
     */
    public function transaction(Closure $callback)
    {
        if ($this->pdo->inTransaction())
            $this->pdo->commit();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, FALSE);
        $this->pdo->beginTransaction();

        // We'll simply execute the given callback within a try / catch block
        // and if we catch any exception we can rollback the transaction
        // so that none of the changes are persisted to the database.
        try
        {
            $result = $callback($this);

            $this->pdo->commit();
        }

            // If we catch an exception, we will roll back so nothing gets messed
            // up in the database. Then we'll re-throw the exception so it can
            // be handled how the developer sees fit for their applications.
        catch (\Exception $e)
        {
            $this->pdo->rollBack();
            throw $e;
        }

        return $result;
    }

    /**
	 * Get the default query grammar instance.
	 *
	 * @return Illuminate\Database\Query\Grammars\Grammars\Grammar
	 */
	protected function getDefaultQueryGrammar()
	{
		return $this->withTablePrefix(new Query\Grammars\FirebirdGrammar);
	}

	/**
	 * Get the default schema grammar instance.
	 *
	 * @return Illuminate\Database\Schema\Grammars\Grammar
	 */
	protected function getDefaultSchemaGrammar()
	{
		return null;
		// $this->withTablePrefix(new Schema\Grammars\FirebirdGrammar);
	}

}

class FireBirdConnectionException extends \Exception {

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param Exception $previous
     */
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}