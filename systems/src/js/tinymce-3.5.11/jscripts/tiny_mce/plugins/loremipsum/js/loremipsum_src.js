/**
 * Lorem Ipsum plug-in for TinyMCE version 3.x
 * -------------------------------------------
 * $Id: loremipsum_src.js 10 2009-04-30 23:20:50Z scholzj $
 *
 * @author     JAkub Scholz
 * @version    $Rev: 10 $
 * @package    LoremIpsum
 * @link       http://www.assembla.com/spaces/lorem-ipsum
 */

var arrLoremIpsumTexts=new Array();
var arrLoremIpsumLangs=new Array();

arrLoremIpsumTexts.push('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.|Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.|Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.|Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');
arrLoremIpsumLangs.push('Latin 1');

arrLoremIpsumTexts.push('Non eram nescius, Brute, cum, quae summis ingeniis exquisitaque doctrina philosophi Graeco sermone tractavissent, ea Latinis litteris mandaremus, fore ut hic noster labor in varias reprehensiones incurreret.|Nam quibusdam, et iis quidem non admodum indoctis, totum hoc displicet philosophari.|Quidam autem non tam id reprehendunt, si remissius agatur, sed tantum studium tamque multam operam ponendam in eo non arbitrantur.|Erunt etiam, et ii quidem eruditi Graecis litteris, contemnentes Latinas, qui se dicant in Graecis legendis operam malle consumere.|Postremo aliquos futuros suspicor, qui me ad alias litteras vocent, genus hoc scribendi, etsi sit elegans, personae tamen et dignitatis esse negent.|Contra quos omnis dicendum breviter existimo.|Quamquam philosophiae quidem vituperatoribus satis responsum est eo libro, quo a nobis philosophia defensa et collaudata est, cum esset accusata et vituperata ab Hortensio.|Qui liber cum et tibi probatus videretur et iis, quos ego posse iudicare arbitrarer, plura suscepi veritus ne movere hominum studia viderer, retinere non posse.|Qui autem, si maxime hoc placeat, moderatius tamen id volunt fieri, difficilem quandam temperantiam postulant in eo, quod semel admissum coerceri reprimique non potest, ut propemodum iustioribus utamur illis, qui omnino avocent a philosophia, quam his, qui rebus infinitis modum constituant in reque eo meliore, quo maior sit, \mediocritatem desiderent.|Sive enim ad sapientiam perveniri potest, non paranda nobis solum ea, sed fruenda etiam [sapientia] est; sive hoc difficile est, tamen nec modus est ullus investigandi veri, nisi inveneris, et quaerendi defatigatio turpis est, cum id, quod quaeritur, sit pulcherrimum.|Etenim si delectamur, cum scribimus, quis est tam invidus, qui ab eo nos abducat? sin laboramus, quis est, qui alienae modum statuat industriae?|Nam ut Terentianus Chremes non inhumanus, qui novum vicinum non vult \'fodere aut arare aut aliquid ferre denique\' -- non enim illum ab industria, sed ab inliberali labore deterret --, sic isti curiosi, quos offendit noster minime nobis iniucundus labor.|Iis igitur est difficilius satis facere, qui se Latina scripta dicunt contemnere.|In quibus hoc primum est in quo admirer, cur in gravissimis rebus non delectet eos sermo patrius, cum idem fabellas Latinas ad verbum e Graecis expressas non inviti legant.|Quis enim tam inimicus paene nomini Romano est, qui Ennii Medeam aut Antiopam Pacuvii spernat aut reiciat, quod se isdem Euripidis fabulis delectari dicat, Latinas litteras oderit?|Synephebos ego, inquit, potius Caecilii aut Andriam Terentii quam utramque Menandri legam?|A quibus tantum dissentio, ut, cum Sophocles vel optime scripserit Electram, tamen male conversam Atilii mihi legendam putem, de quo Lucilius: \'ferreum scriptorem\', verum, opinor, scriptorem tamen, ut legendus sit.|Rudem enim esse omnino in nostris poetis aut inertissimae segnitiae est aut fastidii delicatissimi.|Mihi quidem nulli satis eruditi videntur, quibus nostra ignota sunt. an \'Utinam ne in nemore ...\' nihilo minus legimus quam hoc idem Graecum, quae autem de bene beateque vivendo a Platone disputata sunt, haec explicari non placebit Latine?|Quid?|Si nos non interpretum fungimur munere, sed tuemur ea, quae dicta sunt ab iis quos probamus, eisque nostrum iudicium et nostrum scribendi ordinem adiungimus, quid habent, cur Graeca anteponant iis, quae et splendide dicta sint neque sint conversa de Graecis?|Nam si dicent ab illis has res esse tractatas, ne ipsos quidem Graecos est cur tam multos legant, quam legendi sunt.|Quid enim est a Chrysippo praetermissum in Stoicis?|Legimus tamen Diogenem, Antipatrum, Mnesarchum, Panaetium, multos alios in primisque familiarem nostrum Posidonium. quid?|Theophrastus mediocriterne delectat, cum tractat locos ab Aristotele ante tractatos?|Quid?|Epicurei num desistunt de isdem, de quibus et ab Epicuro scriptum est et ab antiquis, ad arbitrium suum scribere?|Quodsi Graeci leguntur a Graecis isdem de rebus alia ratione compositis, quid est, cur nostri a nostris non legantur?');
arrLoremIpsumLangs.push('Latin 2');

var arrSentences = new Array();

function loremIpsumGimmeSentences(howmany, selText)     {
	var ret = '';
	var arrTmp = new Array();

        //var arrSentences = new Array();
        //arrSentences.push(arrLoremIpsumTexts[selText].split('|'));

	for (var i=0; i < howmany; i++)        {
		arrTmp.push(arrSentences[selText][0]);
		arrSentences[selText].push(arrSentences[selText].shift());
		//make first sentence array element last,
		//in order to avoid paragraphs and list begin always with the same sentence
	}
	ret=arrTmp.join(' ');
	return ret;
}

var LoremIpsumDialog = {
	init : function() {
                var f = document.forms[0];

                var sentences = document.getElementById("sentences");

                for (var i=0; i < arrLoremIpsumTexts.length; i++)       {
                        arrSentences.push(arrLoremIpsumTexts[i].split('|'));
                }//split text to sentences. Sentences limited by '|'

                for (var i=0; i < arrSentences.length; i++) {
                        var tmp=new Array();
                        tmp=(arrSentences[i][0]).split(' ');
                        tmp=tmp.slice(0,3);
                        var tmpText=tmp.join(' ');
                        sentences.options[i]=new Option(arrLoremIpsumLangs[i] + ': ' + tmpText,i);
                }

	},

	insert : function() {
		// Insert the contents from the input into the document
                var insertedText = '';

                var sentences = document.getElementById("sentences");
                var amount = document.getElementById("amount");
                var formating = document.getElementById("formating");

                //count=formObj.lorem_count.value;
                //selectedText=formObj.lorem_select.value;
                //window.alert("bbb");
                switch (formating.value)        {
                        case '0': //unformatted

                                insertedText = loremIpsumGimmeSentences(amount.value, sentences.value);
                        break;
                        case '1': // rte
                        		insertedText += '<h1>Lorem ipsum dolor sit amet</h1>';
                        		insertedText += '<h2>Consectetur adipisicing</h2>';
                        		insertedText += '<h3>Eiusmod tempor incid</h3>';
                        		
                                for (var counter = 0; counter < amount.value; counter++){
                                        countElem = Math.round((Math.random() * 5) + 3); // so many sentences in paragraph
                                        insertedText += '<p>' + loremIpsumGimmeSentences(countElem, sentences.value) + ' <strong>Minim veniam quis</strong></p>';
                                }
                                insertedText += '<ul>';
                                for (var counter = 0; counter < amount.value; counter++)  {
                                        countElem = Math.round(Math.random() + 1); //so many sentences in listelement
                                        insertedText += '<li>' + loremIpsumGimmeSentences(countElem, sentences.value) + '</li>';
                                }
                                insertedText+='</ul>';
                                insertedText += '<ol>';
                                for (var counter = 0; counter < amount.value; counter++)      {
                                        countElem = Math.round(Math.random() + 1); //so many sentences in listelement
                                        insertedText += '<li>' + loremIpsumGimmeSentences(countElem, sentences.value) + '</li>';
                                }
                                insertedText += '</ol>';
                        break;
                }

		tinyMCEPopup.editor.execCommand('mceInsertContent', false, insertedText);
		tinyMCEPopup.close();
	}
};

tinyMCEPopup.requireLangPack();
tinyMCEPopup.onInit.add(LoremIpsumDialog.init, LoremIpsumDialog);