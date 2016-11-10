/* Copr. (c) 2011, 4Mads */
var Parallel=CompositeEffect.extend({setPosition:function(a){this._super(a);for(var b in this._mapEffects)this._mapEffects[b].setPosition(a)}})