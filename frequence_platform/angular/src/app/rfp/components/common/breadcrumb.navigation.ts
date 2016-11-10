import {Component, AfterViewInit, Input, Output, EventEmitter} from "@angular/core";
import {StepsCompletionService} from "../../services/stepscompletion.service";
import {PermissionsDataModel} from "../../models/permissionsdatamodel";
import {NAVIGATION} from "../../../shared/constants/builder.constants";

declare var _: any;
@Component({
    selector: 'breadcrumb',
    templateUrl: '/angular/build/app/views/rfp/common/breadcrumb.html'
})
export class BreadCrumb implements AfterViewInit {

    @Input() currentMenu: string;

    @Output("to-gate") toGate = new EventEmitter<any>();
    @Output("to-targets") toTargets = new EventEmitter<any>();
    @Output("to-budget") toBudget = new EventEmitter<any>();
    @Output("to-builder") toBuilder = new EventEmitter<any>();

    private selectedMenu: string;
    private NAVIGATION = NAVIGATION;
    private enablePreview: boolean = false;

    constructor(private stepsCompletionService: StepsCompletionService,
                private permissionsDataModel: PermissionsDataModel) {
    }

    ngAfterViewInit() {
        this.selectedMenu = this.currentMenu;
        this.enablePreview = this.permissionsDataModel.enablePreviewText;
    }

    navigateToGate() {
        if (this.stepsCompletionService.RFPSteps.isGateCleared)
            this.toGate.emit(true);
    }

    navigateToTargets() {
        if (this.stepsCompletionService.RFPSteps.isTargetsCleared)
            this.toTargets.emit(true)
    }

    navigateToBudget() {
        if (this.stepsCompletionService.RFPSteps.isBudgetCleared)
            this.toBudget.emit(true);
    }

    navigateToBuilder() {
        if (this.stepsCompletionService.RFPSteps.isBuilderCleared)
            this.toBuilder.emit(true);
    }

    getClass(menuItem) {
        let class_string = "";
        if (menuItem == this.currentMenu) {
            class_string = "active";
        }
        if (menuItem == this.NAVIGATION.BUDGET && this.currentMenu == this.NAVIGATION.TARGETS) {
            class_string = "check-hidden";
        }
        else if (menuItem == this.NAVIGATION.BUILDER && this.currentMenu != this.NAVIGATION.BUILDER) {
            class_string = "check-hidden";
        }

        return class_string;
    }
}
