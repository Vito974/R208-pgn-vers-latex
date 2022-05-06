<?php  

$entete = "\documentclass{article}
\usepackage{multicol}
\usepackage{array}
\usepackage{makeidx}
\usepackage[skaknew]{chessfss}
\usepackage{texmate}
\usepackage{xskak}
\usepackage[top=1.5cm, bottom=2cm, left=1.5cm, right=1cm,headheight=15pt]{geometry}
\usepackage{adjmulticol}
\usepackage{ragged2e}
\begin{document}

";


   
    function get_string_between($string, $start, $end){
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
    
    function Decoupeur($chemin){//Fonction pour séparer les parties différente d'un même fichier pour traitement

        ini_set("pcre.jit", "0"); //pour autoriser php à utiliser beaucoup de ressource du pc
        $partie = []; //liste vide pour contenir les parties une fois séparé
        $curseur;   //variable vide pour contenir à chaque tour de boucle la partie extraite du fichier
        $PaternPourDecouper = "/.+?(?=\[Event )/i";  //patern regex pour extraire les parties
        //$PaternPourDecouper = "/\[(.*)(?=\[Event )/i";  //patern regex pour extraire les parties
        $fileContent = file_get_contents($chemin); //on récupère le contenu du fichier pgn
        
        $fileContent = str_replace( array( '<br>', '<br />', "\n", "\r" ), array( ' ', ' ', ' ', ' ' ), $fileContent ); //on retire les retrait de ligne
        
        
        while (preg_match($PaternPourDecouper , $fileContent , $debug)==1) { // tant que on reconnait le paterne
            $fileContent = str_replace($debug, "", $fileContent); //on retire le paterne identifié qui correspond à une partie identifié 
            
            array_push($partie, $debug);  //on ajoute la partie retiré a la liste qui contiendra tout les parties
        }
        array_push($partie, [$fileContent]);  //à la fin il ne reste plus qu'une partie dans le fichier d'origine on l'ajoute donc à notre liste
        print_r($partie);
        return $partie;

    }


    function identifieurDeCoup($groupeDeCoup){
        $pattern = "/\d{1,}\.{1,3}\s?(([Oo0]-[Oo0](-[Oo0])?|[KQRBN]?[a-h]?[1-8]?x?[a-h][1-8](\=[QRBN])?[+#]?)(\s?\{.+?\})?(\s(1-0|0-1|1\/2-1\/2))?\s?){1,2}/i";
        $coups;
        preg_match_All($pattern, $groupeDeCoup, $coups);

        return $coups[0];
    }

    function identifieurTagRoster($groupeDeCoup){
        $pattern = '/(\[\s*(\w+)\s*"([^"]*)"\s*\]\s*)+/i';
        $tags;
        $tagsDictionnaire = [];
        preg_match($pattern, $groupeDeCoup, $tags);
        $tags = explode("]",$tags[0]);
        
        foreach ($tags as $value) {
          if (strpos($value, "Event ")) {
            $tagsDictionnaire["Event"] = get_string_between($value, '"', '"');
          }
          elseif (strpos($value, "Site ")) {
            $tagsDictionnaire["Site"] = get_string_between($value, '"', '"');
          }
          elseif (strpos($value, "Date ")) {
            $tagsDictionnaire["Date"] = get_string_between($value, '"', '"');
          }
          elseif (strpos($value, "EventDate ")) {
            $tagsDictionnaire["EventDate"] = get_string_between($value, '"', '"');
          }
          elseif (strpos($value, "Round ")) {
            $tagsDictionnaire["Round"] = get_string_between($value, '"', '"');
          }

          elseif (strpos($value, "Result ")) {
            $tagsDictionnaire["Result"] = get_string_between($value, '"', '"');
          }
          elseif (strpos($value, "White ")) {
            $tagsDictionnaire["White"] = get_string_between($value, '"', '"');
          }
          elseif (strpos($value, "Eco ")) {
            $tagsDictionnaire["Eco"] = get_string_between($value, '"', '"');
          }
          elseif (strpos($value, "WhiteElo ")) {
            $tagsDictionnaire["WhiteElo"] = get_string_between($value, '"', '"');
          }
          elseif (strpos($value, "BlackElo ")) {
            $tagsDictionnaire["BlackElo"] = get_string_between($value, '"', '"');
          }

          elseif (strpos($value, "PlyCount ")) {
            $tagsDictionnaire["PlyCount"] = get_string_between($value, '"', '"');
          }
        }


        return $tagsDictionnaire;
    }
    

    $myfile = fopen("test.tex", "w");
    fwrite($myfile, $entete);
    $parties = Decoupeur("./file2.pgn");
    $compteur2 = 0;


    foreach ($parties as $souspartie) {
        
        $tags = identifieurTagRoster($parties[$compteur2][0]);
        $coups = identifieurDeCoup($parties[$compteur2][0]);
        $compteur = 1;
        $mainline = "\mainline{";
        $commentaire;

    

        fwrite($myfile, "\chessevent{".$tags["Event"]."}\n");

        fwrite($myfile, "\chessopening{".$tags["Site"]."}\n");

        fwrite($myfile, "Date : ".$tags["Date"]."\n");

        fwrite($myfile, "EventDate : ".$tags["EventDate"]."\n");

        fwrite($myfile, "Round : ".$tags["Round"]."\n");

        fwrite($myfile, "Result : ".$tags["Result"]."\n");

        fwrite($myfile, "\whitename{".$tags["White"]."}\n");

        fwrite($myfile, "\blackname{".$tags["Black"]."}\n");

        fwrite($myfile, "\ECO{".$tags["Eco"]."}\n");

        fwrite($myfile, "\whiteelo{".$tags["WhiteElo"]."}\n");

        fwrite($myfile, "\blackelo{".$tags["BlackElo"]."}\n");

        fwrite($myfile, "PlyCount : ".$tags["PlyCount"]."\n");

        fwrite($myfile, "\makegametitle\n\begin{multicols}{2}\n" . "\\" ."noindent\n". "\\" ."newchessgame[id=main]\n\xskakset{style=styleC}\n");
        



        foreach ($coups as $value) {
            $compteur = $compteur + 1;
            if ($compteur%5 == 1) {
                $mainline = $mainline."}\n";
                fwrite($myfile, $mainline);
                $mainline = "\mainline{";
                fwrite($myfile, "\scalebox{0.90}{\chessboard}\\\\\n");
            }

            if(preg_match("/\{(.*?)\}/i" , $value,  $commentaire) == 1) {
                $value = preg_replace("/\{(.*?)\}/i", "", $value);
                $mainline = $mainline.$value." ";
                $mainline = $mainline."}\n";
                fwrite($myfile, $mainline);
                $mainline = "\mainline{";
                $commentaire = get_string_between($commentaire[0], "{", "}");
                fwrite($myfile, "\xskakcomment{\small".'\\'."texttt\justifying{".'\\'."textcolor{darkgray}{~".$commentaire."}}}\n");

            }else {
                $mainline = $mainline.$value." ";
            }
                
            

        }



        fwrite($myfile, "Score : ".$tags["Result"]."\n");
        fwrite($myfile, "\\"."end{multicols}". "\n\\". "newpage\n");

        $compteur2 = $compteur2 + 1;

    }
    
    
    fwrite($myfile, "\\"."end{document}");

    
    
    


   
    
    







    




   
?>    