<?php

   trait ifx_Memorable
   {
       /**
        * Save a copy of this object to memory, to be accessed later
        * @return void
        */
       public function __toMemory()
       {
           $_SESSION[get_called_class()] = get_class_vars(get_called_class());
       }

       /**
        * Load a copy of this object saved using __toMemory
        * @return void
        */
       public function __fromMemory()
       {
           if (!isset($_SESSION[get_called_class()])) {
               return;
           }

           foreach ($_SESSION[get_called_class()] as $Attr => $Value) {
               $this->$Attr = $Value;
           }
       }

       /**
        * Clear anything saved within memory currently
        * @return [type] [description]
        */
       public function __clearMemory()
       {
           unset($_SESSION[get_called_class()]);
           return;
       }
   }
