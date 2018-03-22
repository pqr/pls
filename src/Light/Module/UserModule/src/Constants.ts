export interface SpecialAccessInfo {
    accessKey:string;
    description:string;
}

export interface Module {
    key:string;
    name:string;
    specialAccessList:Array<SpecialAccessInfo>;
}

export interface SectionInfo {
    section:string;
    modules:Array<Module>;
}

export interface BranchOfficeInfo {
    ID: string;
    Name: string;
}

export type ModuleAccess = {[key: string]: number};
export type SpecialAccess = {[key: string]: number};
export type ResponsibleRoles = {[key: string]: boolean};

declare const Light:any;

export const branchOffices:Array<BranchOfficeInfo> = Light.getPageParam('branchoffices');

export const modulesPerSection:Array<SectionInfo> = Light.getPageParam('modulesPerSection');

// Чтобы mobx умел правильно отслеживать состояние объекта moduleAccess, в этом объекте изначально
// должны быть определены все поля. Полями (т.е. ключами) являются имена модулей. Однако, при загрузке из базы на сервере
// если по какому-то модулю у пользователя стоит опция Inherit, то этого ключа не будет в его moduleAccess объекте.
// Решение: создаём объект allModulesAcceess содержащий все возможные ключи (т.е. все модули) с правами доступа -1 (Inherit)
// Позже, когда на экране выбран конкретный пользователь, этот объект дополняется реальными правами пользователя
// с помощью Object.assign
export let allModulesAcceessUser:ModuleAccess = {};
export let allModulesAcceessGroup:ModuleAccess = {};
export let allSpecialAccessUser:SpecialAccess = {};
export let allSpecialAccessGroup:SpecialAccess = {};
modulesPerSection.forEach((sectionInfo) => {
    sectionInfo.modules.forEach((module) => {
        const {key:moduleKey, specialAccessList} = module;
        allModulesAcceessUser[moduleKey] = -1;
        allModulesAcceessGroup[moduleKey] = 0;
        if (specialAccessList) {
            specialAccessList.forEach(({accessKey}) => {
                allSpecialAccessUser[accessKey] = -1;
                allSpecialAccessGroup[accessKey] = 0;
            });
        }
    });
});

export let allResponsibleRoles:ResponsibleRoles = {};
for (let roleKey in Light.getPageParam('userRoles')) {
    allResponsibleRoles[roleKey] = false;
}

export type TabType = 'user'|'group';
