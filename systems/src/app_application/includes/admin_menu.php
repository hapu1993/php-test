<?php
        $libhtml = new Libhtml;

        $links = array(

            'built_in_fields' => array("built_in_fields.php","Built In Fields","",array (
                'hospital'=>$libhtml->local_text['Hospital'],
                'facility'=>$libhtml->local_text['Facility'],
                'ward'=>$libhtml->local_text['Ward'],
                'location'=>$libhtml->local_text['Location']
               
            )),

            
			
			'patients_and_admissions' => array("patients_and_admissions.php","Patients/Admissions","",array (
                'patients'=>'Patients',
                'Admissions'=>'Admissions',
            )),
          
            'help' => array("help.php", "Help", "", array (

			)),

        );

		
