/*
 |  Translator: @arshavinel
 */
;(function (factory) {
   if (typeof(define) == "function" && define.amd) {
       define(function(){
           return function(datetime){ factory(datetime); };
       });
   } else if (typeof(window.tail) != "undefined" && window.tail.DateTime) {
       factory(window.tail.DateTime);
   }
}(function(datetime){
    datetime.strings.register("ro", {
        months: ["Ianuarie", "Februarie", "Martie", "Aprilie", "Mai", "Iunie", "Iulie", "August", "Septembrie", "Octombrie", "Noiembrie", "Decembrie"],
        days:   ["Duminica", "Luni", "Marti", "Miercuri", "Joi", "Vineri", "Sambata"],
        shorts: ["DUM", "LUN", "MAR", "MIE", "JOI", "VIN", "SAM"],
        time:   ["Ora", "Minute", "Secunde"],
        header: ["Selectati o luna", "Selectati un an", "Selectati un deceniu", "Selectati o ora"]
    });

    return datetime;
}));
