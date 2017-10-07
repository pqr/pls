import {action, observable} from "mobx";

export class UsersFilters {

    @observable name = '';
    @observable active: ''|'0'|'1' = '1';
    @observable groupId = '';

    [key: string]: any;

    @action onChange(fieldKey: string, value: string) {
        this[fieldKey] = value;
    }

}