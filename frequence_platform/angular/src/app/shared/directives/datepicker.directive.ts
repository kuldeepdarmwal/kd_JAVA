import { Component, forwardRef, ElementRef, ViewChild, Input, AfterViewInit, OnDestroy, Output, EventEmitter } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/common';
import { UUID } from 'angular2-uuid';
declare var jQuery: any;
declare var Pickadate: any;

@Component({
    selector: 'datepicker',
    template: `<input type="text" [id]="inputId" #input /> <label [attr.for]="inputId">{{label}}</label>`,
    providers: [
        {provide: NG_VALUE_ACCESSOR, useExisting: forwardRef(() => DatePicker), multi: true}
    ]
})
export class DatePicker implements AfterViewInit, OnDestroy, ControlValueAccessor {

    @Input() public options;
    @Input() public label;

    @Output('open') public onOpen: EventEmitter<void> = new EventEmitter<void>();
    @Output('close') public onClose: EventEmitter<void> = new EventEmitter<void>();
    @Output('select') public onSelect: EventEmitter<Date> = new EventEmitter<Date>();

    @ViewChild('input') input: ElementRef;

    private onChange: any = Function.prototype;
    private onTouched: any = Function.prototype;

    private _date: string;
    private datepicker: any;
    private inputId: any;

    constructor(){
        this.inputId = UUID.UUID();
    }

    public ngAfterViewInit(): any {
        let picker = jQuery(this.input.nativeElement).pickadate(this.options);
        this.datepicker = picker.pickadate('picker');

        this.datepicker.on('open', () => {
            this.onTouched();
            this.onOpen.emit(null);
        });
        this.datepicker.on('close', () => {
            jQuery(document.activeElement).blur();
            this.onClose.emit(null);
        });

        this.datepicker.on('set', (value) => {
            if (value.select && this.date != ''){
                value = this.datepicker.get('select', this.options.format);
                this.onChange(value);
                this.onSelect.emit(value);
            } else if (value.clear !== undefined){
                this.onChange(undefined);
            }
        });

        jQuery('#mpq_flight_info_section .flight-product > .collapsible-header').on('click', function(e) {
            jQuery(window).scrollTop(jQuery('#mpq_flight_info_section').offset().top);
        });
    }

    get date(): string {
        return this.datepicker.get('select', this.options.format);
    }

    set date(date: string) {
        this._date = date;
    }

    public ngOnDestroy(): void {
        this.datepicker.off('open', 'close', 'set');
    }

    public writeValue(value: string): void {
        this.date = value;
    }

    public registerOnChange(fn: (_: any) => {}): void {
        this.onChange = fn;
    }

    public registerOnTouched(fn: () => {}): void {
        this.onTouched = fn;
    }

    public set(target, value){
        this.datepicker.set(target, value);
    }

    public setOption(options){
        this.datepicker.set(options);
    }
}
