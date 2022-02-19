<?php

class Model
{

  private static function connect()
  {
    $pdo = new PDO(DB_TYPE.":host=".DB_HOST.";dbname=".DB_NAME.";port=3306", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
  }

  public static function read($query, $params = array())
  {
    $statement = self::connect()->prepare($query);
    $statement->execute($params);
    if (explode(' ', $query)[0] == 'SELECT')
    {
      $data = $statement->fetchAll(PDO::FETCH_ASSOC);
      return $data;
    }
  }

  public static function write($query, $params = array())
  {
    $statement = self::connect()->prepare($query);
    $data = $statement->execute($params);
	return $data;
  }

  public static function result($query, $params = array())
  {
    $statement = self::connect()->prepare($query);
    $statement->execute($params);
    if (explode(' ', $query)[0] == 'SELECT')
    {
      $data = $statement->fetchAll(PDO::FETCH_ASSOC);
      if(isset($data[0]))
      {
        return $data[0];
      } else {
        return $data;
      }
    }
  }

  

}
?>
