$(document).ready(function(){
	courseAccordion();
});

function courseAccordion()
{
	/*Set up the accordion for the units*/

	var sections = $("li.section");
	$.each(sections, function(index,value){
		var title = $(value).find("h3.sectionname").text();
		$(value).find("h3.sectionname").hide();
		$(value).before("<li class='acchead'><h3 class='sectionname'>" + title + "</h3></li>");
	});

	$("li.section").first().parent().accordion({header: "li.acchead",heightStyle: "content", collapsible:true});

	
	/*Add accordion-style toggle functionality to the labels (learning outcomes)*/
	$(".activity.label.modtype_label").attr("onclick","toggleChildAssignments(this)");
        $(".activity.label.modtype_label").click();
}

function toggleChildAssignments(element)
{
	$(element).nextUntil("li.activity.label.modtype_label").slideToggle();
}