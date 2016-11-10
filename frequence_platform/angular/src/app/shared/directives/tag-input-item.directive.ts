import { Component, EventEmitter, Input, Output } from '@angular/core';

@Component({
    selector: 'tag-input-item',
    template:
        `
         <span class="ng2-tag-input-remove"
          (click)="removeTag()">&times;</span>
        {{text}}
        `,

    styles: [`
    :host {
      display: inline-block;
      background: #e1e1e1;
      color: #757575;
      padding: 7px;
      border-radius: 90px;
      margin: 2px 10px 2px 0px;
    }

    :host.ng2-tag-input-item-selected {
      color: white;
      background: #0d8bff;
    }

    .ng2-tag-input-remove {
      cursor: pointer;
      display: inline-block;
      padding: 0 3px;
    }
  `],
    host: {
        '[class.ng2-tag-input-item-selected]': 'selected'
    }
})
export class TagInputItem {
    @Input() selected: boolean;
    @Input() text: string;
    @Input() index: number;
    @Output() tagRemoved: EventEmitter<any> = new EventEmitter();

    constructor() {}

    removeTag() {
        this.tagRemoved.emit(this.index);
    }
}