import * as _ from "lodash";
import { observable, action, computed } from "mobx";
import { serializable, map, primitive, serialize } from "serializr";
import {
    ModuleAccess,
    SpecialAccess,
    allModulesAcceessUser,
    allSpecialAccessUser,
    allResponsibleRoles
} from "../Constants";
import UserModuleStore from "../UserModuleStore";

declare const Light: any;

// Интерфейс для данных получаемых от сервера в качестве json
export type UserData = Partial<User>;

export class User {
    @observable
    @serializable
    ID: number;

    @observable
    @serializable
    Name: string;

    @observable
    @serializable
    Login: string;

    @observable
    @serializable
    Password: string;

    @observable
    @serializable
    Email: string;

    @observable
    @serializable
    InnerPhone: string;

    @observable
    @serializable
    Mobile: string;

    @observable
    @serializable
    BirthDay: number;

    @observable
    @serializable
    BirthMonth: number;

    @observable
    @serializable
    Sex: "" | "M" | "F";

    @observable
    @serializable
    Started: number;

    @observable
    @serializable
    Position: string;

    @observable
    @serializable
    BranchOfficeID: number;

    @observable
    @serializable
    GroupID: number;

    @observable
    @serializable
    Active: 0 | 1;

    @observable
    @serializable(map(primitive()))
    ResponsibleRoles: { [key: string]: boolean };

    @observable
    @serializable(map(primitive()))
    moduleAccess: ModuleAccess;

    @observable
    @serializable(map(primitive()))
    specialAccess: SpecialAccess;

    @observable Idle: string;
    @observable IdleTimestamp: number;

    @observable isSaving: boolean;

    store: UserModuleStore;

    @observable savedData: any;

    [key: string]: any;

    constructor(userData: UserData, store: UserModuleStore) {
        this.fillData(userData);
        this.store = store;

        // Сохраним оригинальные данные при создании объекта в отдельную структуру, чтобы на лету вычислять, есть ли у
        // текущего пользователя какие-то изменения?
        this.savedData = serialize(this);
    }

    @computed
    get isChanged() {
        return !_.isEqual(this.savedData, serialize(this));
    }

    fillData(userData: UserData) {
        this.ID = userData.ID || 0;
        this.Name = userData.Name || "";
        this.Login = userData.Login || "";
        this.Password = userData.Password || "";
        this.Email = userData.Email || "";
        this.InnerPhone = userData.InnerPhone || "";
        this.Mobile = userData.Mobile || "";
        this.BirthDay = userData.BirthDay || 0;
        this.BirthMonth = userData.BirthMonth || 0;
        this.Sex = userData.Sex || "";
        this.Started = userData.Started || 0;
        this.Position = userData.Position || "";
        this.BranchOfficeID = userData.BranchOfficeID || 0;
        this.GroupID = userData.GroupID || 0;

        this.Active = userData.Active === undefined ? 1 : userData.Active;
        this.ResponsibleRoles = { ...allResponsibleRoles, ...(userData.ResponsibleRoles || {}) };
        this.moduleAccess = { ...allModulesAcceessUser, ...(userData.moduleAccess || {}) };
        this.specialAccess = { ...allSpecialAccessUser, ...(userData.specialAccess || {}) };

        this.Idle = userData.Idle || "";
        this.IdleTimestamp = userData.IdleTimestamp || 0;
    }

    @action
    toggleResponsibleRole(roleKey: string) {
        this.ResponsibleRoles[roleKey] = !this.ResponsibleRoles[roleKey];
    }

    @action
    onChange(fieldKey: string, value: any) {
        this[fieldKey] = value;
    }

    @action
    onChangeModuleAccess(moduleKey: string, newAccess: number) {
        this.moduleAccess[moduleKey] = newAccess;
    }

    @action
    onChangeSpecialAccess(accessKey: string, newAccess: number) {
        this.specialAccess[accessKey] = newAccess;
    }

    @action
    changeAccessAll(newAccess: number) {
        for (let moduleKey in this.moduleAccess) {
            this.moduleAccess[moduleKey] = newAccess;
        }

        // В списке specialAccess возможны всего три варианта: Inherit (-1), No (0), Yes (1)
        // Варианты 2 (Write) и 2 (Write + Delete) отсутвует, поэтому нужно предваритено проверить
        if (newAccess > 1) {
            newAccess = 1;
        }
        for (let accessKey in this.specialAccess) {
            this.specialAccess[accessKey] = newAccess;
        }
    }

    validate() {
        let isValid = true;

        if (this.Name.trim() === "") {
            Light.toastError("Empty Name");
            isValid = false;
        }

        if (this.Login.trim() === "") {
            Light.toastError("Empty Login");
            isValid = false;
        }

        if (this.Active && this.Password.trim() === "") {
            Light.toastError("Empty Password");
            isValid = false;
        }

        if (!this.GroupID) {
            Light.toastError("Group not selected");
            isValid = false;
        }

        return isValid;
    }

    @action
    save = () => {
        if (this.isSaving) {
            return;
        }

        if (!this.validate()) {
            return;
        }

        // Установим специальный флаг, чтобы заблокировать кнопку Save
        // Флаг специально находится внутри класса User
        // если пользователь перейдёт к другой записе пока данный пользователь сохраняется, кнопка Save разблокируется (так и должно быть)!
        this.isSaving = true;

        const postData = serialize(this);

        $.post("?a=UserModule&p=Submit", postData)
            .done(
                action((newUserData: any) => {
                    if (!this.ID) {
                        // Этот пользователь был только что добавлен в базу (новый пользователь)
                        // Нужно добавить его и в массив в store
                        this.store.users.push(this);
                    }
                    this.fillData(newUserData);
                    this.isSaving = false;
                    Light.toastSuccess("User " + newUserData.Name + " saved");
                    this.savedData = serialize(this);
                })
            )
            .fail(
                action(() => {
                    this.isSaving = false;
                    Light.toastError("Save failed");
                })
            );
    };
}
