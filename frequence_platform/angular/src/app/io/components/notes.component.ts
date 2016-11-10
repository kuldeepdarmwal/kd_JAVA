import {Component, Input, EventEmitter} from "@angular/core";
import {IODataModel} from "../models/iodatamodel";

@Component({
    selector: 'notes',
    templateUrl: '/angular/build/app/views/io/notes.html'
})
export class NotesComponent {

    @Input('notes') _notes : any;
    
    constructor(private ioDataModel:IODataModel){
        this.loadIOData();
    }

    loadIOData(){

    }
    notesChange(){
	this.ioDataModel.notes = this._notes;
    }
}