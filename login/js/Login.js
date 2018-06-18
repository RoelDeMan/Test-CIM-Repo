'use strict';


//DB API connections
var databaseActivities = "../kvh/db/DatabaseActivities.php";


//the root
var domeinURL = "http://www.roeldeman.nl/kvh/";


//pages
var homePage = domeinURL+"pages/Homepage.html";
var overviewPage = domeinURL+"pages/MyVideos.html";
var registerPage = domeinURL+"pages/Register.html";
var passwordForgottenPage = domeinURL+"pages/passwordForgotten.html";


//check message (error)
var userNotExist = 'Gebruiker bestaat niet of gegevens komen niet overeen';
var userNotAuthorized = 'Uw account is nog niet goedgekeurd';
var everythingIsCorrect = 'Gegevens zijn correct! Uw wordt doorgestuurd';
var noEmailOrPassword = 'Er is geen email en wachtwoord ingevoerd';
var noPassword = 'Er is geen wachtwoord ingevoerd';
var noEmail = 'Er is geen email ingevoerd';


//Error or Confirm colors
var falseColor = '#FF7888';
var standardResetColor = '#CCC';
var trueColor = '#1387B9';


//checks wich key is used
function focusOnloadKeydown(){
    document.addEventListener("keydown", login, false);
}


//Looking i
function checkLoggedIn() {
    jQuery.ajax({
        method: "POST",
        data: {
            'newCookie': true
        },
        url: databaseActivities,
        dataType: "json",
        success: function (result) {
            jQuery.ajax({
                method: "POST",
                data: {
                    'checkLoggedIn': true
                },
                url: databaseActivities,
                dataType: "json",
                success: function (result) {
                    focusOnloadKeydown();
                }
            });
        }
    });
}


//Login Event
function login(e){
    var inputEmail = document.getElementById('email');
    var inputPassword = document.getElementById('password');
    if(e.keyCode === 13 || e.button === 0){
        checkInput(inputEmail.value, inputPassword.value);
    }else{
        return false;
    }
}


//Global div variable for checkInput function
var insertValidResult =  "<div id='validResult'></div>";


//Checks email and password input fields
function checkInput (inputEmail, inputPassword) {
  var insertResult = document.getElementById('insertValidResult');
    insertResult.innerHTML = insertValidResult;
  var validResult = document.getElementById('validResult');
  var borderColorEmail = document.getElementById('email');
  var borderColorPassword = document.getElementById('password');
    validResult.innerHTML = "";
          if(inputEmail.length === 0 && inputPassword.length === 0) {
            validResult.innerHTML = noEmailOrPassword;
            validResult.style.color = falsColor;
            borderColorEmail.style.borderColor = falsColor;
            borderColorPassword.style.borderColor = falsColor;
              return false;
          }else if (inputPassword.length === 0){
            validResult.innerHTML = noPassword;
            validResult.style.color = falsColor;
            borderColorPassword.style.borderColor = falsColor;
            borderColorEmail.style.borderColor = standardResetColor;
                return false;
          }else if (inputEmail.length === 0) {
            validResult.innerHTML = noEmail;
            validResult.style.color = falsColor;
            borderColorEmail.style.borderColor = falsColor;
            borderColorPassword.style.borderColor = standardResetColor;
                return false;
          }else{
            validResult.innerHTML = "";
            insertResult.innerHTML = "";
                getUser(inputEmail, inputPassword);
          }
}


//Email to lowercase
function lowerCase(userEmail){
    var lowerEmail = userEmail.toLowerCase();
    return lowerEmail;
}


//Get user from DB
function getUser(inputEmail, inputPassword){
    var lowerCaseEmail = lowerCase(inputEmail);
        jQuery.ajax({
            method: "POST",
            data: {
                'authenticateUser': true,
                'email': lowerCaseEmail,
                'password': inputPassword
            },
            url: databaseActivities,
            dataType: "json",
            success: function (result) {
                checkUser(result);
            }
        });
}


//Checking if user exist and/or Authorized
function checkUser(result) {
    var insertResult = document.getElementById('insertValidResult');
    insertResult.innerHTML = insertValidResult;
    var validResult = document.getElementById('validResult');
    var borderColorEmail = document.getElementById('email');
    var borderColorPassword = document.getElementById('password');
   if (result === 'unAuthorized') {
       validResult.innerHTML = userNotAuthorized;
       validResult.style.color = falsColor;
       borderColorEmail.style.borderColor = falsColor;
       borderColorPassword.style.borderColor = falsColor;
   }else if (result === 'true' || result === true) {
        borderColorEmail.style.borderColor = standardResetColor;
        borderColorPassword.style.borderColor = standardResetColor;
        validResult.innerHTML = "";
        validResult.innerHTML = everythingIsCorrect;
        validResult.style.color = trueColor;
        var loader = "<div id='loader'></div>";
        var spinner = document.getElementById('spinner');
        spinner.style.display = 'inline';
        spinner.innerHTML = loader;
        setTimeout(function () {
            jQuery.ajax({
                method: "POST",
                data: {
                    'setCorrectSession': true
                },
                url: databaseActivities,
                dataType: "json",
                success: function (result) {
                    console.log(result);
                    window.open(homePage, "_self");
                }
            });
        }, 1000);
    } else {
        validResult.innerHTML = userNotExist;
        validResult.style.color = falsColor;
        borderColorEmail.style.borderColor = falsColor;
        borderColorPassword.style.borderColor = falsColor;
    }
}


//Go to RegisterPage
function openRegisterPage(){
    window.open(registerPage, "_self");
}


//Go to PasswordForgottenPage
function openPasswordForgottenPage(){
    window.open(passwordForgottenPage, "_self");
}
