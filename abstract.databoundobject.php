<?php

abstract class DataBoundObject {

   protected $ID;
   protected $objPDO;
   protected $strTableName;
   protected $arRelationMap;
   protected $blForDeletion;
   protected $blIsLoaded;
   protected $arModifiedRelations;

   abstract protected function DefineTableName();
   abstract protected function DefineRelationMap();

   public function __construct(PDO $objPDO, $id = NULL) {
      $this->strTableName = $this->DefineTableName();
      $this->arRelationMap = $this->DefineRelationMap();
      $this->objPDO = $objPDO;
      $this->blIsLoaded = false;
      if (isset($id)) {
         $this->ID = $id;
      };
      $this->arModifiedRelations = array();
   }

   public function Load() {
      if (isset($this->ID)) {
		$strQuery = "SELECT ";
        foreach ($this->arRelationMap as $key => $value) {
			$strQuery .= "\"" . $key . "\",";
        }
        $strQuery = substr($strQuery, 0, strlen($strQuery)-1);
        $strQuery .= " FROM " . $this->strTableName . " WHERE \"id\" = :eid";
        $objStatement = $this->objPDO->prepare($strQuery);
        $objStatement->bindParam(':eid', $this->ID, PDO::PARAM_INT);
        $objStatement->execute();
        $arRow = $objStatement->fetch(PDO::FETCH_ASSOC);
        foreach($arRow as $key => $value) {
            $strMember = $this->arRelationMap[$key];
            if (property_exists($this, $strMember)) {
                if (is_numeric($value)) {
                   eval('$this->'.$strMember.' = '.$value.';');
                } else {
                   eval('$this->'.$strMember.' = "'.$value.'";');
                };
            };
         };
         $this->blIsLoaded = true;
         $this->LogMessage("select", $strQuery);
      };
   }

   public function Save() {
      if (isset($this->ID)) {
         $strQuery = 'UPDATE "' . $this->strTableName . '" SET ';
         foreach ($this->arRelationMap as $key => $value) {
            eval('$actualVal = &$this->' . $value . ';');
            if (array_key_exists($value, $this->arModifiedRelations)) {
               $strQuery .= '"' . $key . "\" = :$value, ";
            };
         }
         $strQuery = substr($strQuery, 0, strlen($strQuery)-2);
         $strQuery .= ' WHERE "id" = :eid';
         unset($objStatement);
         $objStatement = $this->objPDO->prepare($strQuery);
         $objStatement->bindValue(':eid', $this->ID, PDO::PARAM_INT);
         foreach ($this->arRelationMap as $key => $value) {
            eval('$actualVal = &$this->' . $value . ';');
            if (array_key_exists($value, $this->arModifiedRelations)) {
               if ((is_int($actualVal)) || ($actualVal == NULL)) {
                  $objStatement->bindValue(':' . $value, $actualVal,PDO::PARAM_INT);
               } else {
                  $objStatement->bindValue(':' . $value, $actualVal,PDO::PARAM_STR);
               };
            };
         };
         $objStatement->execute();
         $this->LogMessage("update", $strQuery);
      } else {
         $strValueList = "";
         $strQuery = 'INSERT INTO "' . $this->strTableName . '"(';
         foreach ($this->arRelationMap as $key => $value) {
            eval('$actualVal = &$this->' . $value . ';');
            if (isset($actualVal)) {
               if (array_key_exists($value, $this->arModifiedRelations)) {
                  $strQuery .= '"' . $key . '", ';
                  $strValueList .= ":$value, ";
               };
            };
         }
         $strQuery = substr($strQuery, 0, strlen($strQuery) - 2);
         $strValueList = substr($strValueList, 0, strlen($strValueList) - 2);
         $strQuery .= ") VALUES (";
         $strQuery .= $strValueList;
         $strQuery .= ")";

         unset($objStatement);
         $objStatement = $this->objPDO->prepare($strQuery);
         foreach ($this->arRelationMap as $key => $value) {
            eval('$actualVal = &$this->' . $value . ';');
            if (isset($actualVal)) {   
               if (array_key_exists($value, $this->arModifiedRelations)) {
                  if ((is_int($actualVal)) || ($actualVal == NULL)) {
                     $objStatement->bindValue(':' . $value, $actualVal, PDO::PARAM_INT);
                  } else {
                     $objStatement->bindValue(':' . $value, $actualVal, PDO::PARAM_STR);
                  };
               };
            };
         }
         $objStatement->execute();
         $this->LogMessage("insert", $strQuery);
         //$this->ID = $this->objPDO->lastInsertId($this->strTableName . "_id_seq");
      }
   }
   public function MarkForDeletion() {
      $this->blForDeletion = true;
   }
   
   public function __destruct() {
      if (isset($this->ID)) {   
         if ($this->blForDeletion == true) {
            $strQuery = 'DELETE FROM "' . $this->strTableName . '" WHERE "id" = :eid';
            $objStatement = $this->objPDO->prepare($strQuery);
            $objStatement->bindValue(':eid', $this->ID, PDO::PARAM_INT);   
            $objStatement->execute();
         };
      }
   }

   public function __call($strFunction, $arArguments) {

      $strMethodType = substr($strFunction, 0, 3);
      $strMethodMember = substr($strFunction, 3);
      switch ($strMethodType) {
         case "set":
            return($this->SetAccessor($strMethodMember, $arArguments[0]));
            break;
         case "get":
            return($this->GetAccessor($strMethodMember));   
      };
      return(false);   
   }

   private function SetAccessor($strMember, $strNewValue) {
      if (property_exists($this, $strMember)) {
         if (is_numeric($strNewValue)) { 
            eval('$this->' . $strMember . ' = ' . $strNewValue . ';');
         } else {
            eval('$this->' . $strMember . ' = "' . $strNewValue . '";');
         };
         $this->arModifiedRelations[$strMember] = "1";
         return($this);
      } else {
         return(false);
      };   
   }

   private function GetAccessor($strMember) {
      if ($this->blIsLoaded != true) {
         $this->Load();
      }
      if (property_exists($this, $strMember)) {
         eval('$strRetVal = $this->' . $strMember . ';');
         return($strRetVal);
      } else {
         return(false);
      };   
   }

   private function LogMessage($action, $strQuery) {
      // Guardar en archivo .log
      $LOGDIR = 'c:\Windows\temp\\';

      $logFile = $LOGDIR . 'logPrueba.log';

      $hFile = fopen($logFile, 'a+');//abre y si no existe crea  el archivo

      if(!is_resource($hFile)) {//verifica que el archivo se ha abierto correctamente.
            printf("Unable to open %s for writing. Check file permissions.", $logFile);
            return false;
      }

      $date = date("d/m/y");//crea fecha 
      $time = date("h:i:s");//crea hora
       //." ---- ". $time ." ---- ". $this->ID ." --- ". $action ." ---- ". $strQuery
      $message = $this->ID . "----" . $action . "Objecte realitzat" . $date ;//crea el mensaje a partir de unas variables.
      //
      fwrite($hFile, $message . "\n\n");//sobre escribe 
      fclose($hFile);//cierra el archivo

      // Guardar en base de datos
      // $stmt = $this->objPDO->prepare("INSERT INTO \"saveDoc\"(\"user\", \"date\", \"time\", \"action\", \"query\") VALUES(:user_id, :ddate, :dtime, :daction, :strQuery)");
      // $stmt->bindParam(":user_id", $this->ID);
      // $stmt->bindParam(":ddate", $date);
      // $stmt->bindParam(":dtime", $time);
      // $stmt->bindParam(":daction", $action);
      // $stmt->bindParam(":strQuery", $strQuery);
      
      // if(!$stmt->execute()) return false;

      // return true;
   }
   
}

?>
