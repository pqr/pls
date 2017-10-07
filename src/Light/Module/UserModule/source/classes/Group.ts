import * as _ from "lodash";
import {observable, action, computed} from "mobx";
import {serializable, map, primitive, serialize} from "serializr";
import {ModuleAccess, SpecialAccess, allModulesAcceessGroup, allSpecialAccessGroup} from "../Constants";
import UserModuleStore from "../UserModuleStore";

// Описание json формата от сервера
export interface GroupData {
    ID?:number;
    Name?:string;
    OrderID?:number;
    moduleAccess?: ModuleAccess;
    specialAccess?: SpecialAccess;
}

export class Group {

    @observable @serializable ID: number;
    @observable @serializable Name: string;
    @observable @serializable OrderID: number;

    @observable @serializable(map(primitive())) moduleAccess: ModuleAccess;
    @observable @serializable(map(primitive())) specialAccess: SpecialAccess;

    @observable isSaving: boolean;

    store:UserModuleStore;

    @observable savedData: any;

    [key: string]: any;

    constructor(groupData: GroupData, store:UserModuleStore) {

        this.fillData(groupData);
        this.store = store;

        // Сохраним оригинальные данные при создании объекта в отдельную структуру, чтобы на лету вычислять, есть ли у
        // текущего пользователя какие-то изменения?
        this.savedData = serialize(this);

    }

    @computed get isChanged() {
        return !_.isEqual(this.savedData, serialize(this));
    }

    fillData(groupData: GroupData) {

        this.ID = groupData.ID || 0;
        this.Name = groupData.Name || '';
        this.OrderID = groupData.OrderID || 0;

        this.moduleAccess = {...allModulesAcceessGroup, ...(groupData.moduleAccess || {})};
        this.specialAccess = {...allSpecialAccessGroup, ...(groupData.specialAccess || {})};

    }

    @action onChange(fieldKey: string, value: any) {
        this[fieldKey] = value;
    }

    @action onChangeModuleAccess(moduleKey:string, newAccess:number) {
        this.moduleAccess[moduleKey] = newAccess;
    }

    @action onChangeSpecialAccess(accessKey:string, newAccess:number) {
        this.specialAccess[accessKey] = newAccess;
    }

    @action changeAccessAll(newAccess:number) {

        for (let moduleKey in this.moduleAccess) {
            this.moduleAccess[moduleKey] = newAccess;
        }

        // В списке specialAccess возможны всего два варианта: No (0), Yes (1)
        // Вариант 2 (Write) отсутвует, поэтому нужно предваритено проверить
        if (newAccess === 2) {
            newAccess = 1;
        }
        for (let accessKey in this.specialAccess) {
            this.specialAccess[accessKey] = newAccess;
        }

    }

    validate() {

        let isValid = true;

        if (this.Name.trim() === '') {
            Light.toastError('Empty Group Name');
            isValid = false;
        }

        return isValid;
    }

    @action save = () => {

        if (this.isSaving) {
            return;
        }

        if (!this.validate()) {
            return;
        }

        // Установим специальный флаг на этом шипменте, чтобы заблокировать кнопку Save
        // Флаг специально находится внутри класса Group
        // если пользователь перейдёт к другой записе пока данный пользователь сохраняется, кнопка Save разблокируется (так и должно быть)!
        this.isSaving = true;

        const postData = serialize(this);

        $.post('?a=UserModule&p=Submit', postData)
            .done(action((newUserData: any) => {
                if (!this.ID) {
                    // Эта группа была только что добавлена в базу (новая группа)
                    // Нужно добавить её и в массив в store
                    this.store.groups.push(this);
                }
                this.fillData(newUserData);
                this.isSaving = false;
                Light.toastSuccess('Group ' + newUserData.Name + ' saved');
                this.savedData = serialize(this);
            }))
            .fail(action(() => {
                this.isSaving = false;
            }));

    };

}