DDLayout.models.cells.Panel = DDLayout.models.cells.Row.extend({
    defaults: {
          kind: 'Panel'
        , cssClass: 'panel-collapse collapse'
        , row_type:'accordion-panel'
        , disabled: false
        , fade: false
        , mode: 'panel'
    },
    compound: 'Accordion',
    initialize:function(){
        var self = this;
        DDLayout.models.abstract.Element.prototype.initialize.call(self);
        self.set('cssClass', 'panel-collapse collapse');
    }
});