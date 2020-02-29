<?php
require('abstract.databoundobject.php');
require('class.postgresLoggerBakend.php');
require('class.mapping.php');
require('class.pdofactory.php');



print "Running...<br />";

        $strDSN = "pgsql:dbname=aplicaweb;host=localhost;port=5432;user=postgres;password=";
        $objPDO = PDOFactory::GetPDO($strDSN, "aplicaweb", "", 
            array());
        $objPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $objUser = new LogData($objPDO);


        $objUser->setnombre('Adri')->setnivel('0')->setfecha('1999/07/26 12:00:00')->setapellido('Casado')->Save();

        print "First name is " . $objUser->getnombre() . "<br />";
        print "Last name is " . $objUser->getnivel() . "<br />";

        $recuperaNom = $objUser->getnombre();
        $recuperaNivel = $objUser->getnivel();
        $recuperaFecha = $objUser->getfecha();
        $recuperaApellido = $objUser->getapellido();
        print "Saving...<br />";
        

        $id = $objUser->getID();
        print "ID in database is " . $id . "<br />";

        print "Destroying object...<br />";
        
        unset($objUser);


        $objUser = new LogData2($objPDO);


        $objUser->setnombre($recuperaNom)->setnivel($recuperaNivel)->setfecha($recuperaFecha)->setapellido($recuperaApellido)->Save();

        print "First name is " . $objUser->getnombre() . "<br />";
        print "Last name is " . $objUser->getnivel() . "<br />";

        $recupera = $objUser->getnombre();
        print($recupera);
        print "Saving...<br />";
        //$objUser->Save();

        $id = $objUser->getID();
        print "ID in database is " . $id . "<br />";

        print "Destroying object...<br />";
        
        unset($objUser);
     

?>
