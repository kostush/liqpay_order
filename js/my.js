

function application () {

}
application.prototype.GetLeadStage = function(){
    var current = this;
    BX24.callMethod(
        "crm.status.list",
        {
            order: {"SORT": "ASC"},
            filter: {"ENTITY_ID": "STATUS"}
        },
        function (result) {
            if (result.error())
                console.error(result.error());
            else {
                console.dir(result.data());
                if (result.more())
                    result.next();
            }
           // this.DealStage =result.data();
            app.displayLeadStage(result.data());
        }
    );


}

application.prototype.displayLeadStage = function(message){
    $('#Lead_Stage').html(mesage);

}

app = new application();