import * as modelModule from "@angular/common";

export interface form {
  getData(group:modelModule.ControlGroup): any;
  setData(group:modelModule.ControlGroup, any) : any;
  getForm(): {[key: string]: any;}, extra?: {[key: string]: any;}
}
