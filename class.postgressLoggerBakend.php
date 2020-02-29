<?php



class LogData extends DataBoundObject {

        protected $nombre;
        protected $nivel;
        protected $fecha;
        protected $apellido;

        protected function DefineTableName() {
                return("Usuario");
        }

        protected function DefineRelationMap() {
                return(array(
                		"id" => "ID",
                        "nombre" => "nombre",
                        "nivel" => "nivel",
                        "fecha" => "fecha",
                        "apellido" => "apellido",
                        ));
        }
}

?>
