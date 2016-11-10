import {Component} from "@angular/core";

@Component({
    selector: "modal",
    template: `
        <div>
          <div id="modal1" class="modal">
            <div class="modal-content">
                <h4>Modal Header 1</h4>
                <p>A bunch of text</p>
            </div>
            <div class="modal-footer">
                <a href="#!" class=" modal-action modal-close waves-effect waves-green btn-flat">Save</a>
                <a href="#!" class=" modal-action modal-close waves-effect waves-green btn-flat">Reset</a>
            </div>
          </div>
        </div>
        <style>
            .test{
                        display: block;
                position: absolute;
                /* margin-top: 10%; */
                /* opacity: 1; */
                z-index: 10000;
            }
            .back{
                background: black;
    width: 100%;
    height: 100%;
    position: absolute;
    z-index: 1090;
    top: 0;
    opacity: 0.8;
            }
        </style>
    `
})
export class Modal {

    constructor() {
    }

    printSomething() {
    }
}