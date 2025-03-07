var upd;

$(document).ready(function() 
	{
	var now = new Date;
	var ccm=now.getMonth();
	var ccy=now.getFullYear();
	
	cal='<table id="fc" style="position:absolute;border-collapse:collapse;background:#FFFFFF;border:1px solid #757575;display:none" cellpadding=2><tr><td style="cursor:pointer;"><img id="csubm" src="../script/arrowleftmonth.gif"></td><td colspan=5 id="mns" style="font-weight:bold;font-size:13px;text-align:center;"></td><td style="cursor:pointer;"><img id="caddm" src="../script/arrowrightmonth.gif"></td></tr><tr style="background:#ABABAB;font-size:13px"><td style="text-align:center">Вс</td><td style="text-align:center">Пн</td><td style="text-align:center">Вт</td><td style="text-align:center">Ср</td><td style="text-align:center">Чт</td><td style="text-align:center">Пт</td><td style="text-align:center">Сб</td></tr>';	
	for(var kk=1;kk<=6;kk++) {
		cal+='<tr>';
		for(var tt=1;tt<=7;tt++) {
			num=7 * (kk-1) - (-tt);
			cal+='<td id="v' + num + '" style="background:#FFFFFF;width:20px;height:20px;font-size:12px;border:1px solid #757575;text-align:center;vertical-align:middle">&nbsp;</td>';
		}
		cal+='</tr>';
	}
	cal+='</table>';

	$('body').append(cal);
	$('body').children(':not(#fc)').click (function () {$('#fc').fadeOut(300)});
	$('#caddm').click (function () {ccm++;if (ccm>=12) {ccm=0;ccy++;}	prepcalendar(ccm,ccy);})
	$('#csubm').click (function () {ccm--;	if (ccm<0) {ccm=11;ccy--;}	prepcalendar(ccm,ccy);})
	
	$(".calendar").click(function()
		{
		upd=$(this);
		
		curdtarr=this.value.split('-');
		
		if (curdtarr.length==3) {
			ccm=curdtarr[1]-1;
			ccy=curdtarr[0];
			}
		prepcalendar(ccm,ccy);

		$('#fc').css('left',upd.offset().left).css('top',upd.offset().top+upd.outerHeight()).fadeIn(300);
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
		vx.css('color','#555555');
		vx.html('&nbsp;');
		vx.unbind ();
		vx.css('cursor','default');		
		if ((d >= (cd -(-1))) && (d<=cd-(-marr[cm]))) {
			dip=((d-cd < sd)&&(cm==sccm)&&(cy==sccy))||((cm<sccm)&&(cy==sccy))||(cy<sccy);

			if (dip) vx.css('color','#FF0000');

			vx.bind('mouseover', function () {$(this).css('background','#FFCC66')});
			vx.bind('mouseout', function () {$(this).css('background','#FFFFFF')});
			vx.bind('click', function () {upd.val(calvalarr[$(this).attr('id').substring(1,$(this).attr('id').length)]);$('#fc').css('display','none');});
			vx.css('cursor','pointer');
			vx.html(d-cd);
			calvalarr[d]=cy+'-'+(cm-(-1))+'-'+(d-cd);
		}
	}
}