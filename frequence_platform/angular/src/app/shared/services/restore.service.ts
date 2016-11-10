import {Injectable, EventEmitter} from "@angular/core";
declare var _;

@Injectable()
export class RestoreService<T> {
  originalItem: T;
  currentItem: T;
  setItem (item: T) {
    this.originalItem = item;
    this.currentItem = this.clone(item);
  }
  getItem (): T {
    return this.currentItem;
  }
  restoreItem (): T {
    this.currentItem = this.originalItem;
    return this.getItem();
  }
  clone (item: T): T {
    // shallow clone
    return _.clone(item);
  }
}