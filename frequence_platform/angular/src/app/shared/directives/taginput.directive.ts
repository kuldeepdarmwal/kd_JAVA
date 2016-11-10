import {Component, HostBinding, Input} from "@angular/core";
import {NgControl} from "@angular/common";
import {TagInputItem} from "./tag-input-item.directive";
declare var jQuery : any;

@Component({
    selector: 'tag-input',
    template:
        `<tag-input-item
    [text]="tag"
    [index]="index"
    [selected]="selectedTag === index"
    (tagRemoved)="_removeTag($event)"
    *ngFor="let tag of tagsList; let index = index">
  </tag-input-item>
  <input
    class="ng2-tag-input-field"
    type="text"
    [placeholder]="placeholder"
    [(ngModel)]="inputValue"
    (paste)="inputPaste($event)"
    (keydown)="inputChanged($event)"
    (blur)="inputBlurred($event)"
    (focus)="inputFocused()"
    #tagInputRef>`,

    styles: [`
    :host {
      display: block;
    }

    :host.ng2-tag-input-focus {
    }

    .ng2-tag-input-field {
        box-shadow: 0 1px #9e9e9e !important;
        border-bottom: none !important;
    }
  `],
    directives: [TagInputItem]
})
export class TagInput {
    @Input() placeholder: string;
    @Input() ngModel: string[];
    @Input() delimiterCode: string = '188';
    @Input() addOnBlur: boolean = true;
    @Input() addOnEnter: boolean = true;
    @Input() addOnPaste: boolean = true;
    @Input() allowedTagsPattern: RegExp = /.+/;
    @HostBinding('class.ng2-tag-input-focus') isFocussed;

    public tagsList: string[];
    public inputValue: string = '';
    public delimiter: number;
    public selectedTag: number;

    constructor(private _ngControl: NgControl) {
        this._ngControl.valueAccessor = this;
    }

    ngOnInit() {
        if (this.ngModel) this.tagsList = this.ngModel;
        this.onChange(this.tagsList);
        this.delimiter = parseInt(this.delimiterCode);
    }

    ngAfterViewInit() {
        // If the user passes an undefined variable to ngModel this will warn
        // and set the value to an empty array
        if (!this.tagsList) {
            console.warn('TagInputComponent was passed an undefined value in ngModel. Please make sure the variable is defined.');
            this.tagsList = [];
            this.onChange(this.tagsList);
        }
    }

    inputChanged(event) {
        let key = event.keyCode;
        switch(key) {
            case 8: // Backspace
                this._handleBackspace();
                break;
            case 13: //Enter
                this.addOnEnter && this._addTags([this.inputValue]);
                event.preventDefault();
                break;

            case this.delimiter:
                this._addTags([this.inputValue]);
                event.preventDefault();
                break;

            default:
                this._resetSelected();
                break;
        }
    }

    inputBlurred(event) {
        this.addOnBlur && this._addTags([this.inputValue]);
        this.isFocussed = false;
    }
    inputFocused(event) {
        this.isFocussed = true;
    }

    inputPaste(event) {
        let clipboardData = event.clipboardData || (event.originalEvent && event.originalEvent.clipboardData);
        event.preventDefault();
        let pastedString = clipboardData.getData('text/plain');
        let htmlString = clipboardData.getData('text/html');
        let tags = [];
        if(htmlString != ""){
            tags = this._splitHTMLString(htmlString);
        }else{
            tags = this._splitString(pastedString);
        }
        let tagsToAdd = tags.filter((tag) => this._isTagValid(tag));
        this._addTags(tagsToAdd);
        this.inputValue = '';
    }

    private _splitHTMLString(htmlString:string){
        let tags : string[] = [];
        var tds = jQuery(htmlString).find('td');
        tds.each(function(index, val){
            tags.push(jQuery(val).text().trim());
        });
        return tags;
    }

    private _splitString(tagString: string) {
        tagString = tagString.trim();
        let tags = tagString.split(String.fromCharCode(44));
        return tags.filter((tag) => !!tag.trim());
    }

    private _isTagValid(tagString: string) {
        return this.allowedTagsPattern.test(tagString);
    }

    private _addTags(tags: string[]) {
        let validTags = tags.filter((tag) => this._isTagValid(tag));
        this.tagsList = this.tagsList.concat(validTags);
        this._resetSelected();
        this._resetInput();
        this.onChange(this.tagsList);
    }

    private _removeTag(tagIndexToRemove) {
        this.tagsList.splice(tagIndexToRemove, 1);
        this._resetSelected();
        this.onChange(this.tagsList);
    }

    private _handleBackspace() {
        if (!this.inputValue.length && this.tagsList.length) {
            if (!(this.selectedTag === undefined || this.selectedTag === null)) {
                this._removeTag(this.selectedTag);
            }
            else {
                this.selectedTag = this.tagsList.length - 1;
            }
        }
    }

    private _resetSelected() {
        this.selectedTag = null;
    }

    private _resetInput() {
        this.inputValue = '';
    }

    /** Implemented as part of ControlValueAccessor. */
    onChange: (value) => any = (value) => { };

    onTouched: () => any = () => { };

    writeValue(value: any) { }

    registerOnChange(fn: any) {
        this.onChange = fn;
    }

    registerOnTouched(fn: any) {
        this.onTouched = fn;
    }
}
