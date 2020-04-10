$(document).ready(function init()
                  {
                    displayBat();
                    displayOption();
                    urlGenerator();
                    simulGetData();
                    maskOption();
                  });

/************************************************************************************/
/***************** Traitement de l'affichage des options et donnees *****************/
/************************************************************************************/

function displayOption()
{
  var metier = $('input[type=radio][name=metier]:checked').attr('value');

  if (metier == 0)
  {
    $("#bonusEclaireur").css("display","none");
    $("#statut").css("display","none");
    $("#statut2").css("display","inline");
    $("#blocOpti").css("display","none");
    $("#blocCorrect").css("display","block");
    $("#furtif").removeAttr('checked');
    calc();
  }
  else if (metier == 1)
  {
    $("#bonusEclaireur").css("display","none");
    $("#statut").css("display","inline");
    $("#statut2").css("display","none");
    $("#blocOpti").css("display","block");
    $("#blocCorrect").css("display","none");
    $("#furtif").removeAttr('checked');
    calc();
  }
  else if (metier == 2)
  {
    $("#bonusEclaireur").css("display","inline");
    $("#statut").css("display","none");
    $("#statut2").css("display","inline");
    $("#blocOpti").css("display","none");
    $("#blocCorrect").css("display","block");
  }
}

/************************************************************************************/
/************ Traitement de l'affichage des batiments en fonction des km ************/
/************************************************************************************/

function displayBat()
{
  var km = parseInt($("#km").val()); //Distance en km

  if (km >= 1 && km <= 28)
  {
    var liste = '<option value="0" selected>-------------------Aucun-------------------</span></option>';
    if (km >= 1 && km <= 24) {liste += '<option value="1">Bâtiment non-déterré</option>';}
    if (km >= 10 && km <= 13) {liste += '<option value="10">Abri anti-atomique</option>';}
    if (km >= 6 && km <= 9) {liste += '<option value="43">Abri de chantier</option>';}
    if (km >= 2 && km <= 5) {liste += '<option value="36">Ambulance</option>';}
    if (km >= 12 && km <= 15) {liste += '<option value="48">Ancien Aerodrome</option>';}
    if (km >= 6 && km <= 9) {liste += '<option value="8">Ancien commissariat</option>';}
    if (km >= 4 && km <= 7) {liste += '<option value="24">Ancien Velib</option>';}
    if (km >= 5 && km <= 8) {liste += '<option value="22">Armurerie Guns\'N\'Zombies</option>';}
    if (km >= 6 && km <= 9) {liste += '<option value="38">Atomic\' Café</option>';}
    if (km >= 16 && km <= 19) {liste += '<option value="55">Avant-Poste Militaire</option>';}
    if (km >= 5 && km <= 8) {liste += '<option value="9">Bar Miteux</option>';}
    if (km >= 6 && km <= 9) {liste += '<option value="45">Bibliothèque de quartier</option>';}
    if (km >= 5 && km <= 8) {liste += '<option value="19">BricoTout</option>';}
    if (km >= 5 && km <= 28) {liste += '<option value="100">Bunker abandonné (ruine)</option>';}
    if (km >= 8 && km <= 11) {liste += '<option value="46">Bureau de poste</option>';}
    if (km >= 6 && km <= 9) {liste += '<option value="44">Cabane de jardin</option>';}
    if (km >= 2 && km <= 5) {liste += '<option value="33">Cache de contrebandiers</option>';}
    if (km >= 2 && km <= 5) {liste += '<option value="35">Camion en panne</option>';}
    if (km >= 16 && km <= 19) {liste += '<option value="56">Camion mairie-mobile</option>';}
    if (km >= 3 && km <= 6) {liste += '<option value="7">Carcasses de voitures</option>';}
    if (km >= 4 && km <= 7) {liste += '<option value="2">Carlingue d\'avion</option>';}
    if (km >= 3 && km <= 6) {liste += '<option value="30">Carrière effondrée</option>';}
    if (km >= 3 && km <= 6) {liste += '<option value="28">Caveau familial</option>';}
    if (km >= 3 && km <= 6) {liste += '<option value="27">Caverne</option>';}
    if (km >= 16 && km <= 19) {liste += '<option value="57">Caverne anciennement habitée</option>';}
    if (km >= 5 && km <= 8) {liste += '<option value="21">Centrale hydraulique</option>';}
    if (km >= 4 && km <= 7) {liste += '<option value="15">Chantier à l\'abandon</option>';}
    if (km >= 21 && km <= 28) {liste += '<option value="58">Char d\'assaut en panne</option>';}
    if (km >= 3 && km <= 6) {liste += '<option value="37">Cimetière indien</option>';}
    if (km >= 3 && km <= 6) {liste += '<option value="16">Ecole maternelle brulée</option>';}
    if (km >= 2 && km <= 5) {liste += '<option value="34">Entrepôt désaffecté</option>';}
    if (km >= 6 && km <= 9) {liste += '<option value="41">Epicerie Fargo</option>';}
    if (km >= 6 && km <= 9) {liste += '<option value="18">Fast Food</option>';}
    if (km >= 10 && km <= 13) {liste += '<option value="53">Gare de triage désertée</option>';}
    if (km >= 15 && km <= 18) {liste += '<option value="47">Hangars de stockage</option>';}
    if (km >= 5 && km <= 28) {liste += '<option value="102">Hôpital abandonné (ruine)</option>';}
    if (km >= 5 && km <= 28) {liste += '<option value="101">Hôtel abandonné (ruine)</option>';}
    if (km >= 10 && km <= 13) {liste += '<option value="49">Immeuble délabré</option>';}
    if (km >= 3 && km <= 6) {liste += '<option value="23">Kebab “Chez Coluche”</option>';}
    if (km >= 2 && km <= 5) {liste += '<option value="6">Laboratoire cosmétique</option>';}
    if (km >= 21 && km <= 28) {liste += '<option value="62">Le bar des illusions perdues</option>';}
    if (km >= 1 && km <= 4) {liste += '<option value="20">Maison d\'un citoyen</option>';}
    if (km >= 4 && km <= 7) {liste += '<option value="40">Meubles kiela</option>';}
    if (km >= 12 && km <= 15) {liste += '<option value="52">Mine effondrée</option>';}
    if (km >= 8 && km <= 11) {liste += '<option value="42">Mini-Market</option>';}
    if (km >= 12 && km <= 15) {liste += '<option value="51">Motel “Dusk”</option>';}
    if (km >= 4 && km <= 7) {liste += '<option value="29">Parc à l\'abandon</option>';}
    if (km >= 3 && km <= 6) {liste += '<option value="14">Parking désaffecté</option>';}
    if (km >= 2 && km <= 5) {liste += '<option value="13">Petit Bois obscur</option>';}
    if (km >= 2 && km <= 5) {liste += '<option value="12">Petite Maison</option>';}
    if (km >= 4 && km <= 7) {liste += '<option value="17">Pharmacie Détruite</option>';}
    if (km >= 17 && km <= 20) {liste += '<option value="60">Puits abandonné</option>';}
    if (km >= 8 && km <= 11) {liste += '<option value="61">Relais autoroutier</option>';}
    if (km >= 4 && km <= 7) {liste += '<option value="31">Route barrée</option>';}
    if (km >= 8 && km <= 11) {liste += '<option value="39">Silos à l\'abandon</option>';}
    if (km >= 5 && km <= 8) {liste += '<option value="11">Stand de fête foraine</option>';}
    if (km >= 4 && km <= 7) {liste += '<option value="5">Supermarché pillé</option>';}
    if (km >= 12 && km <= 15) {liste += '<option value="50">Tente d\'un citoyen</option>';}
    if (km >= 5 && km <= 8) {liste += '<option value="32">Tranchée aménagée</option>';}
    if (km >= 21 && km <= 28) {liste += '<option value="59">Un étrange appareil circulaire</option>';}
    if (km >= 16 && km <= 19) {liste += '<option value="54">Vieil hôpital de campagne</option>';}
    if (km >= 3 && km <= 6) {liste += '<option value="3">Vieille Pompe Hydraulique</option>';}
    if (km >= 12 && km <= 15) {liste += '<option value="25">Villa de Duke</option>';}
    if (km >= 3 && km <= 6) {liste += '<option value="4">Villa délabrée</option>';}

    $("#batiment").html(liste);
  }

  calc();
}

/************************************************************************************/
/************ Recuperation des donnees et affichage de messages d'erreur ************/
/************************************************************************************/

function calc()
{
  razError();

  var checkError = 0;

  var v14Correction = parseInt(($("#s14").is(":checked")== true)? 1 : 0); //Correction saison 14

  var ville = $('input[type=radio][name=ville]:checked').attr('value'); //Type de ville

  var km = parseInt($("#km").val()); //Distance en km
  if (isNaN(km) || km < 1)
  {
    if (ville == 0 || ville == 2)
    {
      $("#error3").html(" Veuillez entrer un nombre entre 1 et 28.");
      checkError++;
    }
    else if (ville == 1)
    {
      $("#error3").html(" Veuillez entrer un nombre entre 1 et 13.");
      checkError++;
    }
  }
  else if ((ville == 0 || ville == 2) && km > 28)
  {
    $("#error3").html(" Veuillez entrer un nombre entre 1 et 28.");
    checkError++;
  }
  else if (ville == 1 && km > 13)
  {
    $("#error3").html(" Veuillez entrer un nombre entre 1 et 13.");
    checkError++;
  }
  else
  {
    $("#km").val(km);
  }

  var bat = parseInt($("#batiment option:selected").val()); //Type de batiment

  var zomb = parseInt($("#zombie").val()); //Nombre de zombies susceptibles de passer
  if (isNaN(zomb) || zomb < 0)
  {
    $("#error5").html(" Veuillez entrer un nombre plus grand ou égal à zéro.");
    checkError++;
  }
  else
  {
    $("#zombie").val(zomb);
  }

  var ame = parseFloat($("#amenagement").val()); //Nombre d'amenagement

  var OD = parseInt($("#OD").val()); //Nombre d'objet de defense
  if (isNaN(OD) || OD < 0)
  {
    $("#error7").html(" Veuillez entrer un nombre plus grand ou égal à zéro.");
    checkError++;
  }
  else
  {
    $("#OD").val(OD);
  }

  //Nombre de camping deja realise recupere en dernier en raison de la necessite de certaines donnees pour afficher le message d'erreur

  var ceur = parseInt($("#ceur").val()); //Nombre de campeur deja enterre
  if (isNaN(ceur) || ceur < 0 || ceur > 7)
  {
    $("#error9").html(" Veuillez entrer un nombre entre 0 et 7.");
    checkError++;
  }
  else
  {
    $("#ceur").val(ceur);
  }

  var peau = parseInt($("#peau").val()); //Nombre de pelure de peau
  if (isNaN(peau) || peau < 0)
  {
    $("#error10").html(" Veuillez entrer un nombre plus grand ou égal à zéro.");
    checkError++;
  }
  else
  {
    $("#peau").val(peau);
  }

  var tente = parseInt($("#tente").val()); //Nombre de toile de tente
  if (isNaN(tente) || tente < 0)
  {
    $("#error11").html(" Veuillez entrer un nombre plus grand ou égal à zéro.");
    checkError++;
  }
  else
  {
    $("#tente").val(tente);
  }

  var tomb = parseInt(($("#tombe").is(":checked")== true)? 1 : 0); //Tombe creuse
  var nuit = parseInt(($("#nuit").is(":checked")== true)? 1 : 0); //Bonus de nuit
  var furtif = parseInt(($("#furtif").is(":checked")== true)? 1 : 0); //Bonus furtif
  var pro = parseInt(($("#pro").is(":checked")== true)? 1 : 0); //Bonus de campeur pro
  var phar = parseInt(($("#phare").is(":checked")== true)? 1 : 0); //Bonus du phare
  var devast = parseInt(($("#devast").is(":checked")== true)? 1 : 0); //Ville devastee

  var cing = parseInt($("#cing").val()); //Nombre de camping deja realise
  if (isNaN(cing) || cing < 0)
  {
    $("#error8").html(" Veuillez entrer un nombre plus grand ou égal à zéro.");
    checkError++;
  }
  else if (ville == 0 || ville == 1) //RE ou RNE
  {
    $("#cing").val(cing);

    if (pro == 0 && cing > 6) //non campeur pro
    {
      $("#error8").html(" Veuillez entrer un nombre entre 0 et 6 ou cocher la case \"campeur pro\" si vous avez ce pouvoir.");
      checkError++;
    }
    else if (pro == 1 && cing > 7) //campeur pro
    {
      $("#error8").html(" Veuillez entrer un nombre entre 0 et 7.");
      checkError++;
    }
  }
  else if (ville == 2) //pande
  {
    $("#cing").val(cing);

    if (pro == 0 && cing > 4) //non campeur pro
    {
      $("#error8").html(" Veuillez entrer un nombre entre 0 et 4 ou cocher la case \"campeur pro\" si vous avez ce pouvoir.");
      checkError++;
    }
    else if (pro == 1 && cing > 8) //campeur pro
    {
      $("#error8").html(" Veuillez entrer un nombre entre 0 et 8.");
      checkError++;
    }
  }

  /***********************************************************************************/
  /************************* Calcul et affichage du resultat *************************/
  /***********************************************************************************/

  if (checkError == 0)
  {
    var villeVal = typeVille(ville);
    var kmVal = distance(km);
    var zoneVal = zone(bat);
    var zombVal = zombie(zomb, furtif);
    var ameVal = ame;
    var ODVal = calcBonusOD(OD);
    var cingVal = camping(cing, pro, ville);
    var ceurVal = campeur(ceur, v14Correction);
    var peauVal = calcBonusPeau(peau);
    var tenteVal = calcBonusTente(tente);
    var tombVal = calcBonusTomb(tomb);
    var nuitVal = calcBonusNuit(nuit);
    var pharVal = calcBonusPhare(phar);
    var devastVal = calcMalusDevast(devast);

    var result = villeVal + kmVal + zoneVal + zombVal + ameVal + ODVal + cingVal + ceurVal + peauVal + tenteVal + tombVal + nuitVal + pharVal + devastVal;

    result = round100(result);
    var opti = round10(-result);
    var correct = round10(opti - 2);

    if (result >= 0)
    {
      var statut = "Optimal";
    }
    else if (result < 0 && result > -2)
    {
      var statut = "Elevé"
    }
    else if (result <= -2 && result > -4)
    {
      var statut = "Correct";
    }
    else if (result <= -4 && result > -7)
    {
      var statut = "Satisfaisant";
    }
    else if (result <= -7 && result > -10)
    {
      var statut = "Limité";
    }
    else if (result <= -10 && result > -14)
    {
      var statut = "Faible";
    }
    else if (result <= -14 && result > -18)
    {
      var statut = "Très faible";
    }
    else if (result <= -18)
    {
      var statut = "Quasi nul";
    }
    else
    {
      var statut = "Error";
    }

    if (result >= -2)
    {
      var statut2 = "Correct max";
    }
    else
    {
      var statut2 = statut;
    }

    $("#statut").html(statut);
    $("#statut2").html(statut2);
    $("#opti").html(opti);
    $("#correct").html(correct);
  }

  urlGenerator();
}

/************************************************************************************/
/****************************** Traitement des donnees ******************************/
/************************************************************************************/

function typeVille(ville)
{
  if (ville == 0 || ville == 1)
  {
    var villeVal = 0;
  }
  else if (ville == 2)
  {
    var villeVal = -14;
  }

  return villeVal;
}

function distance(km, ville)
{
  if (km == 1)
  {
    return -24;
  }
  else if (km == 2)
  {
    return -19;
  }
  else if (km == 3)
  {
    return -14;
  }
  else if (km == 4)
  {
    return -11;
  }
  else if (km >= 5 && km <= 11)
  {
    return -9;
  }
  else if (km == 12)
  {
    return -8;
  }
  else if (km >= 13 && km <= 14)
  {
    return -7;
  }
  else if (km == 15)
  {
    return -6;
  }
  else if (km >= 16)
  {
    return -5;
  }
}

function zone(bat)
{
  var suicide0 = [28];
  var suicide1 = [60];
  var suicide2 = [0,100,101,102];
  var suicide3 = [37];
  var rien = [5];
  var bien = [2,3,4,6,7,9,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,27,29,30,31,34,35,36,
    38,39,40,41,42,43,44,45,46,47,48,49,51,52,53,54,56,57,59,61];
  var correctes = [32,55,58,62];
  var bonnes = [8,50];
  var ideal = [10];

  if (inArray(bat, suicide0)) //suicide0
  {
    return 2;
  }
  else if (inArray(bat, suicide1)) //suicide1
  {
    return 1;
  }
  else if (inArray(bat, suicide2)) //suicide2
  {
    return 0;
  }
  else if (inArray(bat, suicide3)) //suicide3
  {
    return -5;
  }
  else if (inArray(bat, rien)) //rien
  {
    return 5;
  }
  else if (inArray(bat, bien)) //bien
  {
    return 7;
  }
  else if (inArray(bat, correctes)) //correctes
  {
    return 9;
  }
  else if (inArray(bat, bonnes)) //bonnes
  {
    return 11;
  }
  else if (inArray(bat, ideal)) //ideal2
  {
    return 15;
  }
  else if (bat == 1 || bat == 33) //cas particulie du batiment non deterre et de la cache du contrebandier
  {
    return 8;
  }
  else
  {
    return 0;
  }
}

function zombie(zomb, furtif)
{
  if (furtif == 1)
  {
    var Z = -0.6*zomb;
  }
  else if (furtif == 0)
  {
    var Z = -1.4*zomb;
  }
  return Z;
}

function calcBonusOD(OD)
{
  var O = 1.8*OD;
  return O;
}

function camping(cing, pro, ville)
{
  var C = 0;

  if (ville == 0 || ville == 1) //RE ou RNE
  {
    if (pro == 0) //non campeur pro
    {
      if (cing == 1)
      {
        C = -4;//S8
      }
      else if (cing == 2)
      {
        C = -9;//S8
      }
      else if (cing == 3)
      {
        C = -13;//S8
      }
      else if (cing == 4)
      {
        C = -16;//S8
      }
      else if (cing == 5)
      {
        C = -26;//S8
      }
      else if (cing == 6)
      {
        C = -36;//S8
      }
    }
    else if (pro == 1) //campeur pro
    {
      if (cing == 1)
      {
        C = -2;//S8
      }
      else if (cing == 2)
      {
        C = -4;//S8
      }
      else if (cing == 3)
      {
        C = -8;
      }
      else if (cing == 4)
      {
        C = -10;
      }
      else if (cing == 5)
      {
        C = -12;
      }
      else if (cing == 6)
      {
        C = -16;
      }
      else if (cing == 7)
      {
        C = -26;
      }
      else if (cing == 8)
      {
        C = -37;
      }
    }
  }
  else if (ville == 2) //Pande
  {
    if (pro == 0) //non campeur pro
    {
      if (cing == 1)
      {
        C = -4;
      }
      else if (cing == 2)
      {
        C = -6;
      }
      else if (cing == 3)
      {
        C = -8;
      }
      else if (cing == 4)
      {
        C = -10;
      }
    }
    else if (pro == 1) //campeur pro
    {
      if (cing == 1)
      {
        C = -1;//S8
      }
      else if (cing == 2)
      {
        C = -2;//S8
      }
      else if (cing == 3)
      {
        C = -4;//S8
      }
      else if (cing == 4)
      {
        C = -6;//S8
      }
      else if (cing == 5)
      {
        C = -8;//S8
      }
      else if (cing == 6)
      {
        C = -10;//S8
      }
      else if (cing == 7)
      {
        C = -20;//S8
      }
    }
  }

  return C;
}

function campeur(ceur, v14Correction)
{
  if (ceur == 0)
  {
    var C = 0;
  }
  if (ceur == 1)
  {
    var C = 0;
  }
  else if (ceur == 2)
  {
    var C = -2;
  }
  else if (ceur == 3)
  {
    var C = -5;
    if (v14Correction == 1)
      C -= 1;
  }
  else if (ceur == 4)
  {
    var C = -10;
  }
  else if (ceur == 5)
  {
    var C = -14;
  }
  else if (ceur == 6)
  {
    var C = -14;
    if (v14Correction == 1)
      C -= 6;
  }
  else if (ceur == 7)
  {
    var C = -14;
    if (v14Correction == 1)
      C -= 6;
  }

  return C;
}

function calcBonusPeau(peau)
{
  var P = peau*1;
  return P;
}

function calcBonusTente(tente)
{
  var T = tente*1;
  return T;
}

function calcBonusTomb(tomb)
{
  var T = tomb*1.9;
  return T;
}

function calcBonusNuit(nuit)
{
  var N = nuit*2;
  return N;
}

function calcBonusPhare(phar)
{
  var P = phar*5;
  return P;
}

function calcMalusDevast(devast)
{
  var D = devast*-10;
  return D;
}

/***********************************************************************************/
/************************************** Debug **************************************/
/***********************************************************************************/

function formSup()
{
  var pseudo = $("#pseudo2").val();

  var pseudoLog = $('#pseudoX').html();

  if (pseudoLog != null)
  {
    pseudo = pseudoLog;
  }

  var form = '<h3>Données complémentaires</h3>';
  form += '<form action="" id="additionalData" autocomplete="off">';
  form += '<input type="checkbox" id="confirmation" /><label for="confirmation">J\'ai vérifié que les données encodées dans le formulaire de simulation correspondent à ma situation.</label><br/><br/>';
  form += '<label for="pseudo">Pseudo hordes : </label><input type="text" id="pseudo" value="' + pseudo + '"/><br/><br/>';
  form += '<b>Chance de survie réellement rencontrée :</b><br/>';
  form += '<i>Après avoir cliqué sur “inspecter le secteur”, cochez le choix correspondant à la deuxième ligne de ce qui est indiqué sur hordes.</i><br/>';
  form += '<input type="radio" name="realLuck" id="quasiNulles" value="0" /><label for="quasiNulles">Vous estimez que vos chances de survie ici sont <b>quasi nulles</b>… Autant gober du cyanure tout de suite.</label><br/>';
  form += '<input type="radio" name="realLuck" id="tresFaibles" value="1" /><label for="tresFaibles">Vous estimez que vos chances de survie ici sont <b>très faibles</b>. Peut-être que vous aimez jouer à pile ou face ?</label><br/>';
  form += '<input type="radio" name="realLuck" id="faibles" value="2" /><label for="faibles">Vous estimez que vos chances de survie ici sont <b>faibles</b>. Difficile à dire.</label><br/>';
  form += '<input type="radio" name="realLuck" id="limitees" value="3" /><label for="limitees">Vous estimez que vos chances de survie ici sont <b>limitées</b>, bien que ça puisse se tenter. Mais un accident est vite arrivé…</label><br/>';
  form += '<input type="radio" name="realLuck" id="satisfaisantes" value="4" /><label for="satisfaisantes">Vous estimez que vos chances de survie ici sont à peu près <b>satisfaisantes</b>, pour peu qu\'aucun imprévu ne vous tombe dessus.</label><br/>';
  form += '<input type="radio" name="realLuck" id="correctes" value="5" /><label for="correctes">Vous estimez que vos chances de survie ici sont <b>correctes</b> : il ne vous reste plus qu\'à croiser les doigts !</label><br/>';
  form += '<input type="radio" name="realLuck" id="elevees" value="6" /><label for="elevees">Vous estimez que vos chances de survie ici sont <b>élevées</b> : vous devriez pouvoir passer la nuit ici. (Ermite)</label><br/>';
  form += '<input type="radio" name="realLuck" id="optimales" value="7" /><label for="optimales">Vous estimez que vos chances de survie ici sont <b>optimales</b> : Personne ne vous verrait même en vous pointant du doigt. (Ermite)</label><br/><br/>';
  form += '<b>Toppologie du terrain :</b><br/>';
  form += '<i>Après avoir cliqué sur “inspecter le secteur”, cochez le choix correspondant à la première ligne de ce qui est indiqué sur hordes.</i><br/>';
  form += '<input type="radio" name="topology" id="suicide" value="0" /><label for="suicide">Dormir dans un endroit pareil revient à se suicider, purement et simplement. À vous de voir.</label><br/>';
  form += '<input type="radio" name="topology" id="rien" value="1" /><label for="rien">Rien de notable par ici pour se mettre à l\'abri. Vous vous sentez un peu à découvert…</label><br/>';
  form += '<input type="radio" name="topology" id="rares" value="2" /><label for="rares">Cette zone semble n\'offrir que quelques rares protections « naturelles » pour y passer la nuit. Il va falloir faire avec les moyens du bord.</label><br/>';
  form += '<input type="radio" name="topology" id="bien" value="3" /><label for="bien">En cherchant un peu, on doit bien pouvoir se cacher quelque part.</label><br/>';
  form += '<input type="radio" name="topology" id="correctes2" value="4" /><label for="correctes2">L\'endroit offre des cachettes correctes pour qui voudrait y passer la nuit…</label><br/>';
  form += '<input type="radio" name="topology" id="bonnes" value="5" /><label for="bonnes">Ce secteur offre de bonnes planques en cas de besoin.</label><br/>';
  form += '<input type="radio" name="topology" id="ideal" value="6" /><label for="ideal">Ça semble être l\'endroit idéal pour passer la nuit, les possibilités de cachettes ne manquent pas.</label><br/><br/>';
  form += '<label for="commentaires"><b>commentaires :</b></label><br/>';
  form += '<textarea id="commentaires"></textarea><br/><br/>';
  form += '<button type="button" onclick="send();">Envoyer le rapport d\'erreur</button>';
  form += '</form>';

  $("#debug").html(form);
}

function send()
{
  var checkError = 0;
  var msg = "";

  var check = parseInt(($("#confirmation").is(":checked")== true)? 1 : 0);
  var pseudo = $("#pseudo").val();
  var statut = $('input[type=radio][name=realLuck]:checked').attr('value');
  var topology = $('input[type=radio][name=topology]:checked').attr('value');

  if (check == 0)
  {
    msg += "Veuillez vérifier vos données et cocher la case confirmant cette action. \n";
    checkError++;
  }
  if (pseudo == "")
  {
    msg += "Veuillez encoder votre pseudo. \n";
    checkError++;
  }
  if (typeof statut === "undefined")
  {
    msg += "Veuillez encoder votre statut de survie réel. \n";
    checkError++;
  }
  if (typeof topology === "undefined")
  {
    msg += "Veuillez encoder la topologie du terrain. \n";
    checkError++;
  }

  if (checkError == 0)
  {
    var xhr = getXMLHttpRequest();

    if (xhr == null)
    {
      alert("Votre navigateur ne supporte pas l'objet XMLHTTPRequest, veuillez utiliser le formulaire pour envoyer un rapport d'erreur.");
    }
    else
    {
      //recuperation de donnees a envoyer
      var ville = encodeURIComponent($('input[type=radio][name=ville]:checked').attr('value'));
      var metier = encodeURIComponent($('input[type=radio][name=metier]:checked').attr('value'));
      var km = encodeURIComponent(parseInt($("#km").val()));
      var bat = encodeURIComponent(parseInt($("#batiment option:selected").val()));
      var zomb = encodeURIComponent(parseInt($("#zombie").val()));
      var ame = encodeURIComponent(parseFloat($("#amenagement").val()));
      var OD = encodeURIComponent(parseInt($("#OD").val()));
      var cing = encodeURIComponent(parseInt($("#cing").val()));
      var ceur = encodeURIComponent(parseInt($("#ceur").val()));
      var peau = encodeURIComponent(parseInt($("#peau").val()));
      var tente = encodeURIComponent(parseInt($("#tente").val()));
      var tomb = encodeURIComponent(parseInt(($("#tombe").is(":checked")== true)? 1 : 0));
      var nuit = encodeURIComponent(parseInt(($("#nuit").is(":checked")== true)? 1 : 0));
      var furtif = encodeURIComponent(parseInt(($("#furtif").is(":checked")== true)? 1 : 0));
      var pro = encodeURIComponent(parseInt(($("#pro").is(":checked")== true)? 1 : 0));
      var phar = encodeURIComponent(parseInt(($("#phare").is(":checked")== true)? 1 : 0));
      var devast = encodeURIComponent(parseInt(($("#devast").is(":checked")== true)? 1 : 0));
      var pseudo2 = encodeURIComponent($("#pseudo").val());
      var realStatut = encodeURIComponent($('input[type=radio][name=realLuck]:checked').attr('value'));
      var wrongStatut = encodeURIComponent($("#statut").html());
      var missingAme = encodeURIComponent(parseFloat($("#opti").html()));
      var topology2 = encodeURIComponent($('input[type=radio][name=topology]:checked').attr('value'));
      var commentaires = encodeURIComponent($("#commentaires").val());

      var data = "ville=" + ville + "&metier=" + metier + "&km=" + km + "&bat=" + bat + "&zomb=" + zomb + "&ame=" + ame + "&OD=" + OD +
        "&cing=" + cing+ "&ceur=" + ceur + "&peau=" + peau + "&tente=" + tente + "&tomb=" + tomb + "&nuit=" + nuit + "&furtif=" + furtif +
        "&pro=" + pro + "&phar=" + phar + "&devast=" + devast + "&pseudo=" + pseudo2 + "&realStatut=" + realStatut + "&wrongStatut=" + wrongStatut +
        "&missingAme=" + missingAme + "&topology=" + topology2 + "&commentaires=" + commentaires;

      //envoie des données

      xhr.onreadystatechange = function()
      {
        if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0))
        {
          var button = '<button type="button" onclick="formSup();">Signaler une erreur</button>';
          button += '<input type="text" style="display : none;" value="' + pseudo + '" id="pseudo2" />';

          $("#debug").html(button);

          alert("Vos données ont correctement été envoyées.");
        }
      };

      xhr.open("POST", "debug.php", true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.send(data);
    }
  }
  else
  {
    alert(msg);
  }
}

/***********************************************************************************/
/************************* Importation/exportation de data *************************/
/***********************************************************************************/

function urlGenerator()
{
  var debutUrl = document.location.href;
  debutUrl = debutUrl.substring(0, debutUrl.lastIndexOf("/"));

  var s14Correction = parseInt(($("#s14").is(":checked")== true)? 1 : 0); //Correction saison 14
  var ville = $('input[type=radio][name=ville]:checked').attr('value');
  var metier = $('input[type=radio][name=metier]:checked').attr('value');
  var km = parseInt($("#km").val());
  var bat = parseInt($("#batiment option:selected").val());
  var zomb = parseInt($("#zombie").val());
  var ame = parseFloat($("#amenagement").val());
  var OD = parseInt($("#OD").val());
  var cing = parseInt($("#cing").val());
  var ceur = parseInt($("#ceur").val());
  var peau = parseInt($("#peau").val());
  var tente = parseInt($("#tente").val());
  var tomb = parseInt(($("#tombe").is(":checked")== true)? 1 : 0); //Tombe creuse
  var nuit = parseInt(($("#nuit").is(":checked")== true)? 1 : 0); //Bonus de nuit
  var furtif = parseInt(($("#furtif").is(":checked")== true)? 1 : 0); //Bonus furtif
  var pro = parseInt(($("#pro").is(":checked")== true)? 1 : 0); //Bonus de campeur pro
  var phar = parseInt(($("#phare").is(":checked")== true)? 1 : 0); //Bonus du phare
  var devast = parseInt(($("#devast").is(":checked")== true)? 1 : 0); //Ville devastee

  var url = debutUrl + "/index.php?";
  url += "s14Corr=" + s14Correction;
  url += "&ville=" + ville;
  url += "&metier=" + metier;
  url += "&km=" + km;
  url += "&bat=" + bat;
  url += "&z=" + zomb;
  url += "&ame=" + ame;
  url += "&od=" + OD;
  url += "&cg=" + cing;
  url += "&cr=" + ceur;
  url += "&pdp=" + peau;
  url += "&tdt=" + tente;
  url += "&tomb=" + tomb;
  url += "&nuit=" + nuit;
  url += "&furtif=" + furtif;
  url += "&pro=" + pro;
  url += "&phar=" + phar;
  url += "&devast=" + devast;

  $("#url").val(url);
}

function simulGetData()
{
  var s14CorrGet = getURLParameter('s14Corr');
  var villeGet = getURLParameter('ville');
  var metierGet = getURLParameter('metier');
  var kmGet = getURLParameter('km');
  var batGet = getURLParameter('bat');
  var zGet = getURLParameter('z');
  var ameGet = getURLParameter('ame');
  var odGet = getURLParameter('od');
  var cgGet = getURLParameter('cg');
  var crGet = getURLParameter('cr');
  var pdpGet = getURLParameter('pdp');
  var tdtGet = getURLParameter('tdt');
  var tombGet = getURLParameter('tomb');
  var nuitGet = getURLParameter('nuit');
  var furtifGet = getURLParameter('furtif');
  var proGet = getURLParameter('pro');
  var pharGet = getURLParameter('phar');
  var devastGet = getURLParameter('devast');

  if (villeGet != null || metierGet != null || kmGet != null || batGet != null || zGet != null || ameGet != null || odGet != null || cgGet != null || crGet != null ||
    pdpGet != null || tdtGet != null || tombGet != null || nuitGet != null || furtifGet != null || proGet != null || pharGet != null || devastGet != null )
  {
    if ((villeGet == 0 || villeGet == 1 || villeGet == 2) && (metierGet == 0 || metierGet == 1 || metierGet == 2) && kmGet >= 1 && kmGet <= 28 &&
      ((batGet >= 0 && batGet <= 62 && batGet != 26) || (batGet >= 100 && batGet <= 102)) && zGet >= 0 && ameGet >= 0 && odGet >= 0 && cgGet >= 0 &&
      crGet >= 0 && pdpGet >= 0 && tdtGet >= 0 && (tombGet == 0 || tombGet == 1) && (nuitGet == 0 || nuitGet == 1) && (furtifGet == 0 || furtifGet == 1) &&
      (proGet == 0 || proGet == 1) && (pharGet == 0 || pharGet == 1) && (devastGet == 0 || devastGet == 1))
    {
      if (s14CorrGet == 0 || s14CorrGet == 1)
      {
        if (s14CorrGet == 0){$("#s14").removeAttr('checked');}
        else if (s14CorrGet == 1){$("#s14").attr({checked: true});}
      }

      $('input[type=radio][name=ville][value=' + villeGet + ']').attr({checked: true});
      $('input[type=radio][name=metier][value=' + metierGet + ']').attr({checked: true});
      $("#km").val(kmGet);
      displayBat();
      $('select[id=batiment] option[value=' + batGet + ']').attr({selected: true});
      $("#zombie").val(zGet);
      $("#amenagement").val(ameGet);
      $("#OD").val(odGet);
      $("#cing").val(cgGet);
      $("#ceur").val(crGet);
      $("#peau").val(pdpGet);
      $("#tente").val(tdtGet);

      if (tombGet == 0){$("#tombe").removeAttr('checked');}
      else if (tombGet == 1){$("#tombe").attr({checked: true});}

      if (nuitGet == 0){$("#nuit").removeAttr('checked');}
      else if (nuitGet == 1){$("#nuit").attr({checked: true});}

      if (furtifGet == 1 && metierGet == 2){$("#furtif").attr({checked: true});}
      else{$("#furtif").removeAttr('checked');}

      if (proGet == 0){$("#pro").removeAttr('checked');}
      else if (proGet == 1){$("#pro").attr({checked: true});}

      if (pharGet == 0){$("#phare").removeAttr('checked');}
      else if (pharGet == 1){$("#phare").attr({checked: true});}

      if (devastGet == 0){$("#devast").removeAttr('checked');}
      else if (devastGet == 1){$("#devast").attr({checked: true});}

      displayOption();
      calc();
    }
    else
    {
      return;
    }
  }
  else
  {
    return;
  }
}

function importer()
{
  var xhr = getXMLHttpRequest();

  if (xhr == null)
  {
    alert("Votre navigateur ne supporte pas l'objet XMLHTTPRequest, veuillez le mettre à jour ou en utiliser un autre.");
  }
  else
  {
    //envoie des données

    xhr.onreadystatechange = function()
    {
      if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0))
      {
        var string = xhr.responseText;

//				$("#corps").html(string);

        var regex = /<div id="corps">string\(\d{0,8}\)."((?:.|\s)*?)"\s<\/div>/;
        var dataText = string.match(regex)[1];
        var dataJson = JSON.parse(dataText);

//				$("#corps").html(dataText);

//				$("#corps").html(data);

        //Initialisation des variables du succes de l'importation des donnees

        var villeImport = jobImport = kmImport = batImport = zImport = nuitImport = phareImport = devastImport = true;

        //Recupération des donnees dans l'API json

        if(isset(dataJson, 'map', 'city', 'hard')){var hardJson = dataJson['map']['city']['hard'];}
        else{villeImport = false;}

        if(isset(dataJson, 'map', 'hei')){var tailleJson = dataJson['map']['hei'];}
        else{villeImport = false;}

        if(isset(dataJson, 'job')){var jobJson = dataJson['job'];}
        else{jobImport = false;}

        if(isset(dataJson, 'map', 'city', 'x')){var villeXJson = dataJson['map']['city']['x'];}
        else{kmImport = false;}
        if(isset(dataJson, 'map', 'city', 'y')){var villeYJson = dataJson['map']['city']['y'];}
        else{kmImport = false;}

        if(isset(dataJson, 'x')){var xJson = dataJson['x'];}
        else{kmImport = false; batImport = false; zImport = false;}

        if(isset(dataJson, 'y')){var yJson = dataJson['y'];}
        else{kmImport = false; batImport = false; zImport = false;}

        if (isset(dataJson, 'map', 'zones') && batImport && zImport)
        {
          var zonesJson = new Array();

          if (typeof(dataJson['map']['zones'][0])=='undefined')
          {
            zonesJson[0] = new Array();

            zonesJson[0].x = dataJson['map']['zones']['x'];
            zonesJson[0].y = dataJson['map']['zones']['y'];
            zonesJson[0].z = dataJson['map']['zones']['z'];

            if(dataJson['map']['city']['building'] != null)
            {
              zonesJson[0].id = dataJson['map']['zones']['building']['type'];
              zonesJson[0].dig = dataJson['map']['zones']['building']['dig'];
            }
            else
            {
              zonesJson[0].id = 0;
              zonesJson[0].dig = 0;
            }
          }
          else
          {
            for (var i=0; i<dataJson['map']['zones'].length; i++)
            {
              zonesJson[i] = new Array();

              zonesJson[i].x = dataJson['map']['zones'][i]['x'];
              zonesJson[i].y = dataJson['map']['zones'][i]['y'];
              zonesJson[i].z = dataJson['map']['zones'][i]['z'];

              if(dataJson['map']['zones'][i]['building'] != null)
              {
                zonesJson[i].id = dataJson['map']['zones'][i]['building']['type'];
                zonesJson[i].dig = dataJson['map']['zones'][i]['building']['dig'];
              }
              else
              {
                zonesJson[i].id = 0;
                zonesJson[i].dig = 0;
              }
            }
          }
        }
        else
        {
          batImport = false; zImport = false;
        }

        //traiement campeur pro?

        if (villeImport)
        {
          if (hardJson)
          {
            phareImport = false;
          }
        }
        if (isset(dataJson, 'map', 'city', 'buildings'))
        {
          var nameChantiersJson = new Array();

          if (typeof(dataJson['map']['city']['buildings'][0])==='undefined')
          {
            nameChantiersJson[0] = dataJson['map']['city']['buildings']['name'];
          }
          else
          {
            for (var i=0; i<dataJson['map']['city']['buildings'].length; i++)
            {
              nameChantiersJson[i] = dataJson['map']['city']['buildings'][i]['name'];
            }
          }
        }
        else
        {
          phareImport = false;
        }

        if(isset(dataJson, 'map', 'city', 'devast')){var devastJson = dataJson['map']['city']['devast'];}
        else{devastImport = false;}

        //Determination des valeurs pour Camping predict

        if(villeImport)
        {
          if (hardJson)
          {
            var ville = 2;
          }
          else
          {
            if(tailleJson < 20)
            {
              var ville = 1;
            }
            else
            {
              var ville = 0;
            }
          }
        }

        if (jobImport)
        {
          if (jobJson == "eclair")
          {
            var job = 2;
          }
          else if (jobJson == "hunter")
          {
            var job = 1;
          }
          else
          {
            var job = 0;
          }
        }

        if (kmImport)
        {
          var km = Math.round(Math.sqrt((villeXJson-xJson)*(villeXJson-xJson)+(villeYJson-yJson)*(villeYJson-yJson)));

          if (km == 0)
          {
            kmImport = batImport = zImport = false;
          }
        }

        if(batImport && zImport)
        {
          var zoneIntrouvable = true;
          for (var i = 0; i < zonesJson.length; i++)
          {
            if (zonesJson[i].x == xJson && zonesJson[i].y == yJson)
            {
              if(zonesJson[i].dig == 0)
              {
                var bat = zonesJson[i].id;
              }
              else
              {
                var bat = 64;
              }

              var z = zonesJson[i].z;

              zoneIntrouvable = false;
            }
          }
          if (zoneIntrouvable)
          {
            batImport = false; zImport = false;
          }
        }

        var date = new Date;
        var h = date.getHours();
        if (h >= 7 && h < 19)
        {
          var nuit = 0;
        }
        else
        {
          var nuit = 1;
        }

        if(phareImport)
        {
          var phareConstruit = false;
          for (var i = 0; i < nameChantiersJson.length; i++)
          {
            if (nameChantiersJson[i] == 'phare')
            {
              phareConstruit = true;
            }
          }
          if (phareConstruit)
          {
            var phare = 1;
          }
          else
          {
            var phare = 0;
          }
        }

        if (devastImport)
        {
          if(devastJson)
          {
            var devast = 1;
          }
          else
          {
            var devast = 0;
          }
        }

        //Insertion des valeurs

        raz();

        $("#legendImport").css("display","block");

        if (villeImport)
        {
          if (ville == 0)
          {
            $('#re').attr('checked', 'checked');
          }
          else if (ville == 1)
          {
            $('#rne').attr('checked', 'checked');
          }
          else if (ville == 2)
          {
            $('#pande').attr('checked', 'checked');
          }
          $('#xmlImportResult1').html('<b style="color:#A2B154;">&nbsp;&nbsp;V</b>');
        }
        else
        {
          $('#xmlImportResult1').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>');
        }

        if (jobImport)
        {
          if (job == 0)
          {
            $('#habitant').attr('checked', 'checked');
            $("#bonusEclaireur").css("display","none");
            $("#furtif").removeAttr('checked');
          }
          else if (job == 1)
          {
            $('#ermite').attr('checked', 'checked');
            $("#bonusEclaireur").css("display","none");
            $("#furtif").removeAttr('checked');
          }
          else if (job == 2)
          {
            $('#eclaireur').attr('checked', 'checked');
            $("#bonusEclaireur").css("display","inline");
          }
          $('#xmlImportResult2').html('<b style="color:#A2B154;">&nbsp;&nbsp;V</b>');
        }
        else
        {
          $('#xmlImportResult2').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>');
        }

        if (kmImport)
        {
          $('#km').val(km);
          $('#xmlImportResult3').html('<b style="color:#A2B154;">&nbsp;&nbsp;V</b>');
        }
        else
        {
          $('#xmlImportResult3').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>');
        }

        if (batImport)
        {
          displayBat();
          $('select[id=batiment] option[value=' + bat + ']').attr({selected: true});
          $('#xmlImportResult4').html('<b style="color:#A2B154;">&nbsp;&nbsp;V</b>');
        }
        else
        {
          $('#xmlImportResult4').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>');
        }

        if (zImport)
        {
          $("#zombie").val(z);
          $('#xmlImportResult5').html('<b style="color:#A2B154;">&nbsp;&nbsp;V</b>');
        }
        else
        {
          $('#xmlImportResult5').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>');
        }

        $('#xmlImportResult6').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>')
        $('#xmlImportResult7').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>')
        $('#xmlImportResult8').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>')
        $('#xmlImportResult9').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>')
        $('#xmlImportResult10').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>')
        $('#xmlImportResult11').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>')
        $('#xmlImportResult12').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>')

        if (nuit == 0)
        {
          $("#nuit").removeAttr('checked');
        }
        else if (nuit == 1)
        {
          $("#nuit").attr({checked: true});
        }
        $('#xmlImportResult13').html('<b style="color:#A2B154;">&nbsp;&nbsp;V</b>');

        if (job == 2){$('#xmlImportResult14').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>')};

        $('#xmlImportResult15').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>');

        if (phareImport)
        {
          if (phare == 0)
          {
            $("#phare").removeAttr('checked');
          }
          else if (phare == 1)
          {
            $("#phare").attr({checked: true});
          }
          $('#xmlImportResult16').html('<b style="color:#A2B154;">&nbsp;&nbsp;V</b>');
        }
        else
        {
          $('#xmlImportResult16').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>');
        }

        if (devastImport)
        {
          if (devast == 0)
          {
            $("#devast").removeAttr('checked');
          }
          else if (devast == 1)
          {
            $("#devast").attr({checked: true});
          }
          $('#xmlImportResult17').html('<b style="color:#A2B154;">&nbsp;&nbsp;V</b>');
        }
        else
        {
          $('#xmlImportResult17').html('<b style="color:#851717;">&nbsp;&nbsp;X</b>');
        }

        displayOption();
        calc();
      }
    };

    xhr.open("POST", "import_json_data.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send(null);
  }
}

/************************************************************************************/
/******************************** Fonctions diverses ********************************/
/************************************************************************************/

function razError()
{
  $(".error").html("");
}

function raz()
{
  $("#legendImport").css("display","none");

  $("#km").val(1);
  $("#batiment").val("0");
  $("#zombie").val(0);
  $("#amenagement").val(0);
  $("#OD").val(0);
  $("#cing").val(0);
  $("#ceur").val(0);
  $("#peau").val(0);
  $("#tente").val(0);
  $("#tombe").removeAttr('checked');
  $("#nuit").removeAttr('checked');
  $("#furtif").removeAttr('checked');
  $("#pro").removeAttr('checked');
  $("#phare").removeAttr('checked');
  $("#devast").removeAttr('checked');
  $('.xmlImportResult').html('');

  calc();
}

function getXMLHttpRequest()
{
  var xhr = null;

  if (window.XMLHttpRequest || window.ActiveXObject)
  {
    if(window.ActiveXObject)
    {
      try
      {
        xhr = new ActiveXObject("Msxml2.XMLHTTP");
      }
      catch(e)
      {
        xhr = new ActiveXObject("Microsoft.XMLHTTP");
      }
    }
    else
    {
      xhr = new XMLHttpRequest();
    }
  }
  else
  {
    return null;
  }

  return xhr;
}

function round10(val)
{
  var valRound = (Math.round(val*10))/10;
  return valRound;
}

function round100(val)
{
  var valRound = (Math.round(val*100))/100;
  return valRound;
}

function inArray(cible, tableau)
{
  var length = tableau.length;
  for(var i = 0; i < length; i++)
  {
    if(tableau[i] == cible)
    {
      return true;
    }
  }
  return false;
}

function deconnexion()
{
  $("#joueur").html('<div>Joueur</div>');
}

function isset()
{
  var a = arguments.length;

  if (a == 0)
  {
    alert('absence d\'argument dans la fonction.');
  }
  else
  {
    var variable = arguments[0];
    var d = new Array();

    if (arguments.length > 1)
    {
      for (var i = 1; i < arguments.length; i++)
      {
        d[i-1] = arguments[i];
      }
    }

    if(typeof(variable) == 'undefined')
    {
      return false;
    }
    else
    {
      if (d.length == 0)
      {
        return true;
      }
      else
      {
        var var2test = new Array();

        var2test[0] = new Array();

        var2test[0] = variable;

        for (var i = 0; i < d.length; i++)
        {
          if(typeof(var2test[i][d[i]]) == 'undefined')
          {
            return false;
          }
          else
          {
            if (i < d.length-1)
            {
              var2test[i+1] = new Array();
              var2test[i+1] = var2test[i][d[i]];
            }
          }

          if (i == d.length-1)
          {
            return true;
          }
        }
      }
    }
  }
}

function clearXmlImportResult(xmlImportResult)
{
  var target = '#'+ xmlImportResult;
  $(target).html('');
}

function getURLParameter(name)
{
  return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null;
}

/***********************************************************************************/
/********************************** Page move.php **********************************/
/***********************************************************************************/

function maskOption()
{
  var expert = parseInt(($("#simuA").is(":checked")== true)? 1 : 0);

  if (expert == 1)
  {
    $(".expert").css("display","flex");
  }
  else
  {
    $(".expert").css("display","none");
  }
}

function simuWay()
{
  var paBegin = parseInt($("#paBegin").val());
  var eau = parseInt($("#eau").val());
  var b6 = parseInt(($("#b6").is(":checked")== true)? 1 : 0);
  var b7 = parseInt(($("#b7").is(":checked")== true)? 1 : 0);
  var d6 = parseInt($("#d6").val());
  var d8 = parseInt($("#d8").val());
  var alcool = parseInt(($("#alcool").is(":checked")== true)? 1 : 0);
  var cafe = parseInt($("#cafe").val());
  var ss = parseInt(($("#ss").is(":checked")== true)? 1 : 0);
  var hydrotone = parseInt($("#hydrotone").val());

  var soif = parseInt(($("#soif").is(":checked")== true)? 1 : 0);
  var deshydrate = parseInt(($("#deshydrate").is(":checked")== true)? 1 : 0);
  var desaltere = parseInt(($("#desaltere").is(":checked")== true)? 1 : 0);
  var drogue = parseInt(($("#drogue").is(":checked")== true)? 1 : 0);
  var dependant = parseInt(($("#dependant").is(":checked")== true)? 1 : 0);
  var blessure = parseInt(($("#blessure").is(":checked")== true)? 1 : 0);
  var rassasie = parseInt(($("#rassasie").is(":checked")== true)? 1 : 0);
  var ivre = parseInt(($("#ivre").is(":checked")== true)? 1 : 0);
  var gueule_de_bois = parseInt(($("#gueule_de_bois").is(":checked")== true)? 1 : 0);

  var soifEnd = parseInt(($("#soifEnd").is(":checked")== true)? 1 : 0);
  var deshydrateEnd = parseInt(($("#deshydrateEnd").is(":checked")== true)? 1 : 0);
  var desaltereEnd = parseInt(($("#desaltereEnd").is(":checked")== true)? 1 : 0);
  var drogueEnd = parseInt(($("#drogueEnd").is(":checked")== true)? 1 : 0);
  var dependantEnd = parseInt(($("#dependantEnd").is(":checked")== true)? 1 : 0);
  var rassasieEnd = parseInt(($("#rassasieEnd").is(":checked")== true)? 1 : 0);
  var ivreEnd = parseInt(($("#ivreEnd").is(":checked")== true)? 1 : 0);

  var array = new Array("A","B","C","D");
  var x = array.length;
  var text = new Array();

  switch (x)
  {
    case 1:
      iteration1(array,text);
      break;

    case 2:
      iteration2(array,text);
      break;

    case 3:
      iteration3(array,text);
      break;

    case 4:
      iteration4(array,text);
      break;

    default:
  }
}

function iteration1(array,text)
{
  var z = 0;

  text[z] = array[0];
  RemoveDupArray(text);
  alert(text.join(" | ") + " : " + text.length);
}

function iteration2(array,text)
{
  var z = 0;

  for (var j = 0; j < 2; j++)
  {
    var array1 = new Array();
    for (var tab1 = 0; tab1 < array.length; tab1++)
    {
      array1[tab1] = array[tab1];
    }
    array1.splice(j,1);

    text[z] = array[j]+array1[0];
    z++;
  }
  RemoveDupArray(text);
  alert(text.join(" | ") + " : " + text.length);
}

function iteration3(array,text)
{
  var z = 0;

  for (var k = 0; k < 3; k++)
  {
    var array2 = new Array();
    for (var tab2 = 0; tab2 < array.length; tab2++)
    {
      array2[tab2] = array[tab2];
    }
    array2.splice(k,1);

    for (var j = 0; j < 2; j++)
    {
      var array1 = new Array();
      for (var tab1 = 0; tab1 < array2.length; tab1++)
      {
        array1[tab1] = array2[tab1];
      }
      array1.splice(j,1);

      text[z] = array[k]+array2[j]+array1[0];
      z++;
    }
  }
  RemoveDupArray(text);
  alert(text.join(" | ") + " : " + text.length);
}

function iteration4(array,text)
{
  var z = 0;

  for (var l = 0; l < 4; l++)
  {
    var array3 = new Array();
    for (var tab3 = 0; tab3 < array.length; tab3++)
    {
      array3[tab3] = array[tab3];
    }
    array3.splice(l,1);

    for (var k = 0; k < 3; k++)
    {
      var array2 = new Array();
      for (var tab2 = 0; tab2 < array.length; tab2++)
      {
        array2[tab2] = array3[tab2];
      }
      array2.splice(k,1);

      for (var j = 0; j < 2; j++)
      {
        var array1 = new Array();
        for (var tab1 = 0; tab1 < array2.length; tab1++)
        {
          array1[tab1] = array2[tab1];
        }
        array1.splice(j,1);

        text[z] = array[l]+array3[k]+array2[j]+array1[0];
        z++;
      }
    }
  }
  RemoveDupArray(text);
  alert(text.join(" | ") + " : " + text.length);
}

function RemoveDupArray(a)
{
  a.sort();
  for (var i = 1; i < a.length; i++)
  {
    if (a[i-1] === a[i])
      a.splice(i, 1);
  }
}