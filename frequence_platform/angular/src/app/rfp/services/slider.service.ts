import {Injectable, EventEmitter} from '@angular/core';

export class SliderModel {
    position:number
    value:number
}

@Injectable()
export class SliderService {

    slide:EventEmitter<any>;
    slideEnd:EventEmitter<any>;

    constructor() {
        this.slide = new EventEmitter();
        this.slideEnd = new EventEmitter();
    }

    public onSlide(position, value) {
        let sliderModel:SliderModel = <SliderModel>{};
        sliderModel.position = position;
        sliderModel.value = value;
        this.slide.emit(sliderModel);
    }

    public onSlideEnd(position, value) {
        let sliderModel:SliderModel = <SliderModel>{};
        sliderModel.position = position;
        sliderModel.value = value;
        this.slideEnd.emit(sliderModel);
    }

    public getSlideEvent(){
        return this.slide;
    }

    public getSlideEndEvent(){
        return this.slideEnd;
    }

}