import { Component, forwardRef, ElementRef, ViewChild, Input, AfterViewInit, OnDestroy, Output, EventEmitter } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/common';
import { Typecast } from '../pipes/typecast.pipe';
import { NumberFormat } from "../pipes/number_format.pipe";
import { UUID } from 'angular2-uuid';
declare var jQuery: any;

@Component({
    selector: 'input-editable',
    template: ` <span *ngIf="!open" [ngSwitch]="format" class="input-value">
                    <template ngSwitchWhen="currency">{{ value | typecast:'float' | currency:'USD':true:'1.2-2' }}</template>
                    <template ngSwitchWhen="number">{{ value | number_format }}</template>
                    <template ngSwitchDefault>{{ value }}</template>
                </span>
                <a class="open-input" *ngIf="editable && !open" (click)="openInput()">
                    <i class="material-icons">mode_edit</i>
                </a>
                <input [style.display]="editable && open ? 'inline-block' : 'none'" type="text" #input [attr.disabled]="!open ? true : null" [(ngModel)]="value" [class.enabled]="open" />
                <a class="close-input" *ngIf="editable && open" (click)="closeInput()" [class.enabled]="open">
                    <i class="material-icons">done</i>
                </a>`,
    providers: [
        {provide: NG_VALUE_ACCESSOR, useExisting: forwardRef(() => InputEditable), multi: true}
    ],
    pipes: [Typecast, NumberFormat]
})
export class InputEditable implements AfterViewInit, OnDestroy, ControlValueAccessor {

    @Input() public editable: boolean;
    @Input() public format: string;

    @Output('open') public onOpen: EventEmitter<void> = new EventEmitter<void>();
    @Output('close') public onClose: EventEmitter<void> = new EventEmitter<void>();

    @ViewChild('input') input: ElementRef;

    private onChange: any = Function.prototype;
    private onTouched: any = Function.prototype;

    private open: boolean = false;
    private value: any;

    public ngAfterViewInit(): any { }

    private openInput(){
        this.open = true;
        this.onOpen.emit(null);
        this.input.nativeElement.focus();
    }

    private closeInput(){
        this.open = false;
        this.onChange(this.value);
        this.onClose.emit(null);
    }

    public ngOnDestroy(): void {
        //this.datepicker.off('open', 'close', 'set');
    }

    public writeValue(value: string): void {
        this.value = value;
    }

    public registerOnChange(fn: (_: any) => {}): void {
        this.onChange = fn;
    }

    public registerOnTouched(fn: () => {}): void {
        this.onTouched = fn;
    }
}
