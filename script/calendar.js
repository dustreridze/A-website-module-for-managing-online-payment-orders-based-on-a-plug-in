var upd;

$(document).ready(function() 
	{
	var now = new Date;
	var ccm=now.getMonth();
	var ccy=now.getFullYear();
	
	cal='<style>#fc_wrapper {box-shadow: 0 5px 5px rgba(64, 64, 64, 0.5);position:absolute;display:none;border-radius: 6px;border: 2px solid #d9bd8b;padding:0;margin:0;background: #feeed5} #fc {border-collapse:collapse;font-family: Calibri;font-size:16px;} #fc td:not(:first-child) {border-left: 1px solid #d9bd8b;} #fc tr:not(:last-child) td {border-bottom: 1px solid #d9bd8b;} #fc td, #fc th {text-align:center;vertical-align:middle;height:30px;width:30px;}</style><div id="fc_wrapper"><table id="fc" cellpadding=2><tr><th style="cursor:pointer;"><img id="csubm" src="/shared/script/arrowleftmonth.gif"></th><th colspan=5 id="mns" style="font-weight:bold;"></th><th style="cursor:pointer;"><img id="caddm" src="/shared/script/arrowrightmonth.gif"></th></tr><tr style="background:#cba37f"><th>Вс</th><th>Пн</th><th>Вт</th><th>Ср</th><th>Чт</th><th>Пт</th><th>Сб</th></tr>';	
	for(var kk=1;kk<=6;kk++) {
		cal+='<tr>';
		for(var tt=1;tt<=7;tt++) {
			num=7 * (kk-1) - (-tt);
			cal+='<td id="v' + num + '">&nbsp;</td>';
		}
		cal+='</tr>';
	}
	cal+='</table></div>';

	$('body').append(cal);
	$('body').children(':not(#fc_wrapper)').click (function () {$('#fc_wrapper').fadeOut(300)});
	$('#caddm').click (function () {ccm++;if (ccm>=12) {ccm=0;ccy++;}	prepcalendar(ccm,ccy);})
	$('#csubm').click (function () {ccm--;	if (ccm<0) {ccm=11;ccy--;}	prepcalendar(ccm,ccy);})
	
	$(".calendar").click(function()
		{
		upd=$(this);
		
		curdtarr=this.value.split('.');
		
		if (curdtarr.length==3) {
			ccm=curdtarr[1]-1;
			ccy=curdtarr[2];
			}
		prepcalendar(ccm,ccy);

		$('#fc_wrapper').css('left',upd.offset().left+upd.outerWidth()-$(this).width()).css('top',upd.offset().top+upd.outerHeight()+2).fadeIn(300);
		return false;
		});	
	});	

function prepcalendar(cm,cy) {
	var mn=new Array('ЯНВАРЬ','ФЕВРАЛЬ','МАРТ','АПРЕЛЬ','МАЙ','ИЮНЬ','ИЮЛЬ','АВГУСТ','СЕНТЯБРЬ','ОКТЯБРЬ','НОЯБРЬ','ДЕКАБРЬ');
	var mnn=new Array('31','28','31','30','31','30','31','31','30','31','30','31');
	var mnl=new Array('31','29','31','30','31','30','31','31','30','31','30','31');
	var calvalarr=new Array(42);
	
	var now = new Date;	
	var sccm=now.getMonth();
	var sccy=now.getFullYear();
	var sd=now.getDate();
	var td=new Date(cy,cm,1);
	var cd=td.getDay();
	
	$('#mns').html(mn[cm]+ ' ' + cy);
	marr=((cy%4)==0)?mnl:mnn;
	for(var d=1;d<=42;d++) {
		vx=$('#v'+parseInt(d));
		vx.css('color','#3c2610');
		vx.html('&nbsp;');
		vx.unbind ();
		vx.css('cursor','default');		
		if ((d >= (cd -(-1))) && (d<=cd-(-marr[cm]))) {
			dip=((d-cd < sd)&&(cm==sccm)&&(cy==sccy))||((cm<sccm)&&(cy==sccy))||(cy<sccy);

			if (dip) vx.css('color','#aaaaaa');

			vx.bind('mouseover', function () {$(this).css('background','#FFCC66')});
			vx.bind('mouseout', function () {$(this).css('background','#feeed5')});
			vx.bind('click', function () {upd.val(calvalarr[$(this).attr('id').substring(1,$(this).attr('id').length)]);$('#fc_wrapper').css('display','none');});
			vx.css('cursor','pointer');
			vx.html(d-cd);
			pd=(d-cd<10)? '0':'';pm=(cm-(-1)<10)? '0':'';
			calvalarr[d]=pd+(d-cd)+'.'+pm+(cm-(-1))+'.'+cy;
		}
	}
}