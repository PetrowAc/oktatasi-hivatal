<?php
class ScoreCalculation {
    public $exampleData;
    public $selectedUniversity;
    public $matriResults;
    public $selectedResults;
    public $extraPoints;

    //Példa adatok betöltése
    function set_exampleData($val) {
        $this->exampleData = $val;
        $this->selectedUniversity = $this->exampleData['valasztott-szak'];
        $this->matriResults = $this->exampleData['erettsegi-eredmenyek'];
        $this->extraPoints = $this->exampleData['tobbletpontok'];

        //Kötelező tárgyak meglétének illetve eredményeinek ellenőrzése
        $status = 0;
        foreach ($this->matriResults as $key => $value) {
            if($value['nev'] == 'magyar nyelv és irodalom' || $value['nev'] == 'történelem' || $value['nev'] == 'matematika') {
                if(substr($value['eredmeny'], 0, -1) < 20) {
                    return 'Hiba, nem lehetséges a pontszámítás a '.$value['nev'].' tárgyból elért 20% alatti eredmény miatt';
                }
                $status++;
            }
        }

        if($status < 3) return 'Hiba, nem lehetséges a pontszámítás a kötelező érettségi tárgyak hiánya miatt';

        //Választott tárgyak filterezése
        $this->selectedResults = $this->matriResults;

        switch ($this->selectedUniversity['egyetem']) {
            case 'ELTE':
                foreach ($this->selectedResults as $key => $value) {
                    if($value['nev'] == 'matematika'){
                        unset($this->selectedResults[$key]);
                    }
                }
                break;
            case 'PPKE':
                foreach ($this->selectedResults as $key => $value) {
                    if($value['nev'] == 'angol nyelv'){
                        unset($this->selectedResults[$key]);
                    }
                }
                break;
            
            default:
                return 'Hiba, ismeretlen egyetem!';
                break;
        }

        return true;
    }

    //Kötelező tárgyak ellenőrzése
    function checkBaseSubjects(){
        switch ($this->selectedUniversity['egyetem']) {
            case 'ELTE':
                foreach ($this->matriResults as $key => $value) {
                    if($value['nev'] == 'matematika') {
                        if(substr($value['eredmeny'], 0, -1) < 20) {
                            return 'Hiba, nem lehetséges a pontszámítás a '.$value['nev'].' tárgyból elért 20% alatti eredmény miatt';
                        }
                        return $value;
                    }
                }
                return 'Hiba, nem lehetséges a pontszámítás a kötelező érettségi tárgyak hiánya miatt';
                break;

            case 'PPKE':
                foreach ($this->matriResults as $key => $value) {
                    if($value['nev'] == 'angol nyelv') {
                        if(substr($value['eredmeny'], 0, -1) < 20) {
                            return 'Hiba, nem lehetséges a pontszámítás a '.$value['nev'].' tárgyból elért 20% alatti eredmény miatt';
                        } else {
                            if($value['tipus'] != 'emelt') {
                                return 'Hiba, a kötelező tárgy nem emelt szintű';
                            }
                        }
                        return $value;
                    }
                }
                return 'Hiba, nem lehetséges a pontszámítás a kötelező érettségi tárgyak hiánya miatt';
                break;
            
            default:
                return 'Hiba, ismeretlen egyetem!';
                break;
        }
    }

    //Kötelező választott tárgyak ellenőrzése
    function checkSelectedSubjects() {
        //Választott tárgyak ellenőrzése
        switch ($this->selectedUniversity['egyetem']) {
            case 'ELTE':
                //Egyetem szerinti választott tárgyak szűrése
                foreach ($this->selectedResults as $key => $value) {
                    if($value['nev'] == 'biológia' || $value['nev'] == 'fizika' || $value['nev'] == 'informatika' || $value['nev'] == 'kémia') {
                        $filteredSelectedResults[$key] = $value;
                    }
                }

                if(!isset($filteredSelectedResults)) return 'Hiba, nincs az egyetemnek megfelelő választott tárgy!';

                //Szűrt választott tárgyak közötti legnagyobb kiválasztása
                $max = 0;
                foreach ($filteredSelectedResults as $key => $value) {
                    if(substr($value['eredmeny'], 0, -1) > $max) {
                        $max = substr($value['eredmeny'], 0, -1);
                        $filteredMax = $value;
                    }
                }

                if($max < 20) return 'Hiba, a legtöbb pontszámú választott tárgy sem éri el a 20%-ot!'; else return $filteredMax;
                
                break;
            case 'PPKE':
                //Egyetem szerinti választott tárgyak szűrése
                foreach ($this->selectedResults as $key => $value) {
                    if($value['nev'] == 'francia' || $value['nev'] == 'német' || $value['nev'] == 'olasz' || $value['nev'] == 'orosz' || $value['nev'] == 'spanyol' || $value['nev'] == 'történelem') {
                        $filteredSelectedResults[$key] = $value;
                    }
                }

                if(!isset($filteredSelectedResults)) return 'Hiba, nincs az egyetemnek megfelelő választott tárgy!';

                //Szűrt választott tárgyak közötti legnagyobb kiválasztása
                $max = 0;
                foreach ($filteredSelectedResults as $key => $value) {
                    if(substr($value['eredmeny'], 0, -1) > $max) {
                        $max = substr($value['eredmeny'], 0, -1);
                        $filteredMax = $value;
                    }
                }

                if($max < 20) return 'Hiba, a legtöbb pontszámú választott tárgy sem éri el a 20%-ot!'; else return $filteredMax;
                
                break;
            
            default:
                return 'Hiba, ismeretlen egyetem!';
                break;
        }
    }

    //Alap pontszám kiszámítása
    function defaultPoint() {
        $basePoints = $this->checkBaseSubjects();
        $selectedPoints = $this->checkSelectedSubjects();

        if(is_array($basePoints)) {
            if(is_array($selectedPoints)) {
                $totalPoint = (substr($basePoints['eredmeny'], 0, -1) + substr($selectedPoints['eredmeny'], 0, -1)) * 2;
                return $totalPoint;
            } else { return $selectedPoints; } //Amennyibe hiba történt a választott tárgyak ellenőrzése közben
        } else { return $basePoints; } //Amennyiben hiba történt a kötelező tárgyak ellenőrzése közben
    }

    //Többletpont számítás
    function morePoints() {
        //Emeltszintű vizsgák ellenőrzése
        $examLevelBonus = 0;
        foreach ($this->matriResults as $key => $value) {
            if($value['tipus'] == 'emelt') {
                $examLevelBonus += 50;
            }
        }

        if($examLevelBonus >= 100) {
            return $examLevelBonus; //Amennyiben már az emelt szintű vizsgák végett elérte a maximális 100 többletpontot
        } else {
            //Azonos nyelvből letett vizsgák szűrése (B2, C1 = C1)
            foreach ($this->extraPoints as $key => $value) {
                if($value['kategoria'] == 'Nyelvvizsga' && $value['tipus'] == 'C1') {
                    foreach ($this->extraPoints as $key2 => $value2) {
                        if($value2['kategoria'] == 'Nyelvvizsga' && $value2['nyelv'] == $value['nyelv'] && $value2['tipus'] == 'B2') {
                            unset($this->extraPoints[$key2]);
                        }
                    }
                }
            }

            //Pontszám hozzáadás a nyelvvizsga szintjétől fűggően
            $langBonus = 0;
            foreach ($this->extraPoints as $key => $value) {
                switch ($value['tipus']) {
                    case 'B2':
                        $langBonus += 28;
                        break;
                    
                    case 'C1':
                        $langBonus += 40;
                        break;
                    
                    default:
                        return 'Hiba, ismeretlen típusú nyelvvizsga!';
                        break;
                }
            }

            if(($examLevelBonus + $langBonus) >= 100) return 100; else return $examLevelBonus + $langBonus;
        }
    }
}

// output: 470 (370 alappont + 100 többletpont)
$exampleData = [
    'valasztott-szak' => [
        'egyetem' => 'ELTE',
        'kar' => 'IK',
        'szak' => 'Programtervező informatikus',
    ],
    'erettsegi-eredmenyek' => [
        [
            'nev' => 'magyar nyelv és irodalom',
            'tipus' => 'közép',
            'eredmeny' => '70%',
        ],
        [
            'nev' => 'történelem',
            'tipus' => 'közép',
            'eredmeny' => '80%',
        ],
        [
            'nev' => 'matematika',
            'tipus' => 'közép',
            'eredmeny' => '90%',
        ],
        [
            'nev' => 'angol nyelv',
            'tipus' => 'közép',
            'eredmeny' => '90%',
        ],
        [
            'nev' => 'informatika',
            'tipus' => 'közép',
            'eredmeny' => '95%',
        ],
    ],
    'tobbletpontok' => [
        [
            'kategoria' => 'Nyelvvizsga',
            'tipus' => 'B2',
            'nyelv' => 'angol',
        ],
        [
            'kategoria' => 'Nyelvvizsga',
            'tipus' => 'C1',
            'nyelv' => 'német',
        ],
    ],
];

// output: 480 (380 alappont + 100 többletpont)
$exampleData2 = [
    'valasztott-szak' => [
        'egyetem' => 'PPKE',
        'kar' => 'BTK',
        'szak' => 'Anglisztika',
    ],
    'erettsegi-eredmenyek' => [
        [
            'nev' => 'magyar nyelv és irodalom',
            'tipus' => 'közép',
            'eredmeny' => '70%',
        ],
        [
            'nev' => 'történelem',
            'tipus' => 'közép',
            'eredmeny' => '80%',
        ],
        [
            'nev' => 'matematika',
            'tipus' => 'emelt',
            'eredmeny' => '90%',
        ],
        [
            'nev' => 'angol nyelv',
            'tipus' => 'emelt',
            'eredmeny' => '94%',
        ],
        [
            'nev' => 'francia',
            'tipus' => 'közép',
            'eredmeny' => '96%',
        ],
        [
            'nev' => 'fizika',
            'tipus' => 'közép',
            'eredmeny' => '98%',
        ],
    ],
    'tobbletpontok' => [
        [
            'kategoria' => 'Nyelvvizsga',
            'tipus' => 'B2',
            'nyelv' => 'angol',
        ],
        [
            'kategoria' => 'Nyelvvizsga',
            'tipus' => 'C1',
            'nyelv' => 'német',
        ],
    ],
];

// output: hiba, nem lehetséges a pontszámítás a kötelező érettségi tárgyak hiánya miatt
$exampleData3 = [
    'valasztott-szak' => [
        'egyetem' => 'ELTE',
        'kar' => 'IK',
        'szak' => 'Programtervező informatikus',
    ],
    'erettsegi-eredmenyek' => [
        [
            'nev' => 'matematika',
            'tipus' => 'emelt',
            'eredmeny' => '90%',
        ],
        [
            'nev' => 'angol nyelv',
            'tipus' => 'közép',
            'eredmeny' => '94%',
        ],
        [
            'nev' => 'informatika',
            'tipus' => 'közép',
            'eredmeny' => '95%',
        ],
    ],
    'tobbletpontok' => [
        [
            'kategoria' => 'Nyelvvizsga',
            'tipus' => 'B2',
            'nyelv' => 'angol',
        ],
        [
            'kategoria' => 'Nyelvvizsga',
            'tipus' => 'C1',
            'nyelv' => 'német',
        ],
    ],
];

// output: hiba, nem lehetséges a pontszámítás a magyar nyelv és irodalom tárgyból elért 20% alatti eredmény miatt
$exampleData4 = [
    'valasztott-szak' => [
        'egyetem' => 'ELTE',
        'kar' => 'IK',
        'szak' => 'Programtervező informatikus',
    ],
    'erettsegi-eredmenyek' => [
        [
            'nev' => 'magyar nyelv és irodalom',
            'tipus' => 'közép',
            'eredmeny' => '15%',
        ],
        [
            'nev' => 'történelem',
            'tipus' => 'közép',
            'eredmeny' => '80%',
        ],
        [
            'nev' => 'matematika',
            'tipus' => 'emelt',
            'eredmeny' => '90%',
        ],
        [
            'nev' => 'angol nyelv',
            'tipus' => 'közép',
            'eredmeny' => '94%',
        ],
        [
            'nev' => 'informatika',
            'tipus' => 'közép',
            'eredmeny' => '95%',
        ],
    ],
    'tobbletpontok' => [
        [
            'kategoria' => 'Nyelvvizsga',
            'tipus' => 'B2',
            'nyelv' => 'angol',
        ],
        [
            'kategoria' => 'Nyelvvizsga',
            'tipus' => 'C1',
            'nyelv' => 'német',
        ],
    ],
];



//-------- Példa adatbázis kiválasztása --------
    $currentData = $exampleData;
//----------------------------------------------


$scoreCalculation = new ScoreCalculation();
$setData = $scoreCalculation->set_exampleData($currentData);

if($setData == 1) {
    $defaultPoints = $scoreCalculation->defaultPoint();
    $morePoints = $scoreCalculation->morePoints();

    if(is_int($defaultPoints)) {
        if(is_int($morePoints)) {
            echo '
            <table border="1" style="border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px;">Egyetem:</td>
                    <td style="padding: 5px;">'.$currentData['valasztott-szak']['egyetem'].'</td>
                </tr>
                <tr>
                    <td style="padding: 5px;">Kar:</td>
                    <td style="padding: 5px;">'.$currentData['valasztott-szak']['kar'].'</td>
                </tr>
                <tr>
                    <td style="padding: 5px;">Szak:</td>
                    <td style="padding: 5px;">'.$currentData['valasztott-szak']['szak'].'</td>
                </tr>
                <tr>
                    <td style="padding: 5px;">Alap pont:</td>
                    <td style="padding: 5px;">'.$defaultPoints.'</td>
                </tr>
                <tr>
                    <td style="padding: 5px;">Többletpont:</td>
                    <td style="padding: 5px;">'.$morePoints.'</td>
                </tr>
                <tr>
                    <td style="padding: 5px;">Összesen:</td>
                    <td style="padding: 5px;">'.$defaultPoints+$morePoints.'</td>
                </tr>
            </table>
            ';
        } else {
            echo $morePoints;
        }
    } else {
        echo $defaultPoints;
    }
} else {
    echo $setData;
}

?>