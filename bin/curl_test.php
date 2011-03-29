<?php
// Création d'une ressource cURL
$ch = curl_init();

// Définition de l'URL et autres options appropriées
curl_setopt($ch, CURLOPT_URL, "http://localhost:3001/get_user_info");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);

// Récupération de l'URL et passage au navigateur
$out = curl_exec($ch);
print($out);

// Fermeture de la ressource cURL et libération des ressources systèmes
curl_close($ch);
?>
