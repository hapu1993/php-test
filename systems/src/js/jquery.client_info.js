var nVer = navigator.appVersion;
var nAgt = navigator.userAgent;
var browserName  = navigator.appName;
var fullVersion  = ''+parseFloat(navigator.appVersion); 
var majorVersion = parseInt(navigator.appVersion,10);
var nameOffset,verOffset,ix;

// In MSIE, the true version is after "MSIE" in userAgent
if ((verOffset=nAgt.indexOf("MSIE"))!=-1) {
 browserName = "Microsoft Internet Explorer";
 fullVersion = nAgt.substring(verOffset+5);
}
// In Opera, the true version is after "Opera" 
else if ((verOffset=nAgt.indexOf("Opera"))!=-1) {
 browserName = "Opera";
 fullVersion = nAgt.substring(verOffset+6);
}
//In Chrome, the true version is after "Chrome" 
else if ((verOffset=nAgt.indexOf("Chrome"))!=-1) {
 browserName = "Chrome";
 fullVersion = nAgt.substring(verOffset+7);
}
// In Safari, the true version is after "Safari" 
else if ((verOffset=nAgt.indexOf("Safari"))!=-1) {
 browserName = "Safari";
 fullVersion = nAgt.substring(verOffset+7);
}
// In Firefox, the true version is after "Firefox" 
else if ((verOffset=nAgt.indexOf("Firefox"))!=-1) {
 browserName = "Firefox";
 fullVersion = nAgt.substring(verOffset+8);
}
//In Chromium, the true version is after "Chromium" 
else if ((verOffset=nAgt.indexOf("Chromium"))!=-1) {
 browserName = "Chromium";
 fullVersion = nAgt.substring(verOffset+9);
}
// In most other browsers, "name/version" is at the end of userAgent 
else if ( (nameOffset=nAgt.lastIndexOf(' ')+1) < (verOffset=nAgt.lastIndexOf('/')) ) 
{
 browserName = nAgt.substring(nameOffset,verOffset);
 fullVersion = nAgt.substring(verOffset+1);
 if (browserName.toLowerCase()==browserName.toUpperCase()) {
  browserName = navigator.appName;
 }
}
// trim the fullVersion string at semicolon/space if present
if ((ix=fullVersion.indexOf(";"))!=-1) fullVersion=fullVersion.substring(0,ix);
if ((ix=fullVersion.indexOf(" "))!=-1) fullVersion=fullVersion.substring(0,ix);

majorVersion = parseInt(''+fullVersion,10);
if (isNaN(majorVersion)) {
 fullVersion  = ''+parseFloat(navigator.appVersion); 
 majorVersion = parseInt(navigator.appVersion,10);
}

var OSName="Unknown OS";
if (navigator.appVersion.indexOf("Win")!=-1) OSName="Windows";
if (navigator.appVersion.indexOf("Mac")!=-1) OSName="MacOS";
if (navigator.appVersion.indexOf("X11")!=-1) OSName="UNIX";
if (navigator.appVersion.indexOf("Ubuntu")!=-1) OSName="Ubuntu";
if (navigator.appVersion.indexOf("Linux")!=-1) OSName="Linux";

var screenW = 640, screenH = 600;
if (parseInt(navigator.appVersion)>3) {
 screenW = screen.width;
 screenH = screen.height;
}
else if (navigator.appName == "Netscape" 
    && parseInt(navigator.appVersion)==3
    && navigator.javaEnabled()
   ) 
{
 var jToolkit = java.awt.Toolkit.getDefaultToolkit();
 var jScreenSize = jToolkit.getScreenSize();
 screenW = jScreenSize.width;
 screenH = jScreenSize.height;
}

var winW = 630, winH = 600;
var winW2 = 630, winH2 = 600;

if (parseInt(navigator.appVersion)>3) {
 if (navigator.appName=="Netscape") {
  winW = window.innerWidth;
  winH = window.innerHeight;
  winW2 = window.innerWidth-16;
  winH2 = window.innerHeight-16;
 }
}

winW2 = winW2-80;
winH2 = winH2;

//document.cookie = 'sw='+winW2;
//document.cookie = 'sh='+winH2;

function getTimezoneName() {
	var tmSummer = new Date(Date.UTC(2005, 6, 30, 0, 0, 0, 0));
	var so = -1 * tmSummer.getTimezoneOffset();
	var tmWinter = new Date(Date.UTC(2005, 12, 30, 0, 0, 0, 0));
	var wo = -1 * tmWinter.getTimezoneOffset();
	
	if (-660 == so && -660 == wo) return 'Pacific/Midway';
	if (-600 == so && -600 == wo) return 'Pacific/Tahiti';
	if (-570 == so && -570 == wo) return 'Pacific/Marquesas';
	if (-540 == so && -600 == wo) return 'America/Adak';
	if (-540 == so && -540 == wo) return 'Pacific/Gambier';
	if (-480 == so && -540 == wo) return 'US/Alaska';
	if (-480 == so && -480 == wo) return 'Pacific/Pitcairn';
	if (-420 == so && -480 == wo) return 'US/Pacific';
	if (-420 == so && -420 == wo) return 'US/Arizona';
	if (-360 == so && -420 == wo) return 'US/Mountain';
	if (-360 == so && -360 == wo) return 'America/Guatemala';
	if (-360 == so && -300 == wo) return 'Pacific/Easter';
	if (-300 == so && -360 == wo) return 'US/Central';
	if (-300 == so && -300 == wo) return 'America/Bogota';
	if (-240 == so && -300 == wo) return 'US/Eastern';
	if (-240 == so && -240 == wo) return 'America/Caracas';
	if (-240 == so && -180 == wo) return 'America/Santiago';
	if (-180 == so && -240 == wo) return 'Canada/Atlantic';
	if (-180 == so && -180 == wo) return 'America/Montevideo';
	if (-180 == so && -120 == wo) return 'America/Sao_Paulo';
	if (-150 == so && -210 == wo) return 'America/St_Johns';
	if (-120 == so && -180 == wo) return 'America/Godthab';
	if (-120 == so && -120 == wo) return 'America/Noronha';
	if (-60 == so && -60 == wo) return 'Atlantic/Cape_Verde';
	if (0 == so && -60 == wo) return 'Atlantic/Azores';
	if (0 == so && 0 == wo) return 'Africa/Casablanca';
	if (60 == so && 0 == wo) return 'Europe/London';
	if (60 == so && 60 == wo) return 'Africa/Algiers';
	if (60 == so && 120 == wo) return 'Africa/Windhoek';
	if (120 == so && 60 == wo) return 'Europe/Amsterdam';
	if (120 == so && 120 == wo) return 'Africa/Harare';
	if (180 == so && 120 == wo) return 'Europe/Athens';
	if (180 == so && 180 == wo) return 'Africa/Nairobi';
	if (240 == so && 180 == wo) return 'Europe/Moscow';
	if (240 == so && 240 == wo) return 'Asia/Dubai';
	if (270 == so && 210 == wo) return 'Asia/Tehran';
	if (270 == so && 270 == wo) return 'Asia/Kabul';
	if (300 == so && 240 == wo) return 'Asia/Baku';
	if (300 == so && 300 == wo) return 'Asia/Karachi';
	if (330 == so && 330 == wo) return 'Asia/Calcutta';
	if (345 == so && 345 == wo) return 'Asia/Katmandu';
	if (360 == so && 300 == wo) return 'Asia/Yekaterinburg';
	if (360 == so && 360 == wo) return 'Asia/Colombo';
	if (390 == so && 390 == wo) return 'Asia/Rangoon';
	if (420 == so && 360 == wo) return 'Asia/Almaty';
	if (420 == so && 420 == wo) return 'Asia/Bangkok';
	if (480 == so && 420 == wo) return 'Asia/Krasnoyarsk';
	if (480 == so && 480 == wo) return 'Australia/Perth';
	if (540 == so && 480 == wo) return 'Asia/Irkutsk';
	if (540 == so && 540 == wo) return 'Asia/Tokyo';
	if (570 == so && 570 == wo) return 'Australia/Darwin';
	if (570 == so && 630 == wo) return 'Australia/Adelaide';
	if (600 == so && 540 == wo) return 'Asia/Yakutsk';
	if (600 == so && 600 == wo) return 'Australia/Brisbane';
	if (600 == so && 660 == wo) return 'Australia/Sydney';
	if (630 == so && 660 == wo) return 'Australia/Lord_Howe';
	if (660 == so && 600 == wo) return 'Asia/Vladivostok';
	if (660 == so && 660 == wo) return 'Pacific/Guadalcanal';
	if (690 == so && 690 == wo) return 'Pacific/Norfolk';
	if (720 == so && 660 == wo) return 'Asia/Magadan';
	if (720 == so && 720 == wo) return 'Pacific/Fiji';
	if (720 == so && 780 == wo) return 'Pacific/Auckland';
	if (765 == so && 825 == wo) return 'Pacific/Chatham';
	if (780 == so && 780 == wo) return 'Pacific/Enderbury'
	if (840 == so && 840 == wo) return 'Pacific/Kiritimati';
	return 'US/Pacific';
}

var tz = getTimezoneName();