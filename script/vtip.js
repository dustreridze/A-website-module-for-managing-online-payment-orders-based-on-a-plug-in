function vtip_over(e) {
            this.t = this.title;
            this.title = ''; 
            this.top = (e.pageY + yOffset); this.left = (e.pageX + xOffset);
            
            $('body').append( '<p id="vtip"><img id="vtipArrow" />' + this.t + '</p>' );
                        
            $('p#vtip #vtipArrow').attr("src", 'images/vtip_arrow.png');
            $('p#vtip').css("top", this.top+"px").css("left", this.left+"px").fadeIn("slow");
		}

function vtip_out() {
            this.title = this.t;
            $("p#vtip").fadeOut("slow").remove();
        }

function vtip_move(e) {
            this.top = (e.pageY + yOffset); this.left = (e.pageX + xOffset);
            $("p#vtip").css("top", this.top+"px").css("left", this.left+"px");
        }
		
this.vtip = function() {    
    this.xOffset = -10; // x distance from mouse
    this.yOffset = 10; // y distance from mouse       

	$(".vtip").live('mouseover',vtip_over).live('mouseout',vtip_out).live('mousemove',vtip_move);
};

jQuery(document).ready(function($){vtip();}) 