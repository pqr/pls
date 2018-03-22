import {observable, computed, action} from 'mobx';
import {TabType} from "./Constants";
import {User, UserData} from "./classes/User";
import {Group, GroupData} from "./classes/Group";
import {UsersFilters} from "./classes/UsersFilters";

declare const Light:any;

export default class UserModuleStore {

    @observable tab: TabType = 'user';

    @observable users: Array<User>;
    @observable groups: Array<Group>;

    @observable selectedUser: User | null = null;
    @observable selectedGroup: Group | null = null;

    @observable usersFilters: UsersFilters;
    @observable usersSortFieldKey: string;
    @observable usersSortDirection: 'ASC'|'DESC';

    constructor() {

        this.users = Light.getPageParam('users').map((userData:UserData) => new User(userData, this));
        this.groups = Light.getPageParam('groups').map((groupData:GroupData) => new Group(groupData, this));

        this.usersFilters = new UsersFilters();
        this.usersSortFieldKey = Light.getPageParam('usersSortFieldKey');
        this.usersSortDirection = Light.getPageParam('usersSortDirection');
    }

    @computed get groupsOrderedArray() {
        return this.groups.sort((group1: Group, group2: Group) => {
            if (group1.OrderID  < group2.OrderID) {
                return -1;
            }
            if (group1.OrderID  > group2.OrderID) {
                return 1;
            }
            return 0;
        });
    }

    usersFilterFunc = (user: User) => {

        const usersFilters = this.usersFilters;

        const filterName = usersFilters.name.trim().toLowerCase();
        if (filterName !== ''
            && user.Name.toLowerCase().indexOf(filterName) === -1
            && user.Login.toLowerCase().indexOf(filterName) === -1
            && user.Email.toLowerCase().indexOf(filterName) === -1) {
            return false;
        }

        if (usersFilters.active !== '' && user.Active !== parseInt(usersFilters.active)) {
            return false;
        }

        if (usersFilters.groupId !== '' && user.GroupID !== parseInt(usersFilters.groupId)) {
            return false;
        }

        return true;
    };

    usersSortFunc = (user1: User, user2: User) => {

        const sortFieldKey = this.usersSortFieldKey;
        const sortDirection = this.usersSortDirection;

        const value1 = user1[sortFieldKey];
        const value2 = user2[sortFieldKey];
        if (value1 < value2) {
            return sortDirection === 'ASC' ? -1 : 1;
        }
        if (value1 > value2) {
            return sortDirection === 'ASC' ? 1 : -1;
        }
        return 0;
    };

    @computed get visibleUsers() {
        return this.users.filter(this.usersFilterFunc).sort(this.usersSortFunc);
    }

    @computed get isUsersTabActive() {
        return this.tab === 'user';
    }

    @computed get isGroupsTabActive() {
        return this.tab === 'group';
    }

    @action chageTab(tab: TabType) {
        this.tab = tab;
    }

    @action changeUsersSort(fieldKey: string) {
        if (this.usersSortFieldKey === fieldKey) {
            this.usersSortDirection = this.usersSortDirection === 'ASC' ? 'DESC' : 'ASC';
        } else {
            this.usersSortFieldKey = fieldKey;
            this.usersSortDirection = 'ASC';
        }

        // Отправим ajax запрос на сервер, чтобы сохранить новый выбор пользователя
        Light.setUserParam('UserModuleUsersOrderBy', this.usersSortFieldKey + ' ' + this.usersSortDirection);
    }

    @action selectUser(user: User) {
        this.selectedUser = user;
    }

    @action selectGroup(group: Group) {
        this.selectedGroup = group;
    }

    @action addNewUser() {
        this.selectedUser = new User({}, this);
    }

    @action addNewGroup() {
        this.selectedGroup = new Group({}, this);
    }

    save = () => {
        if (this.isUsersTabActive) {
            if (this.selectedUser) {
                this.selectedUser.save();
            }
        } else {
            if (this.selectedGroup) {
                this.selectedGroup.save();
            }
        }
    };

    @action cancel = () => {

        if (this.isUsersTabActive) {
            if (this.selectedUser) {
                this.selectedUser.fillData(this.selectedUser.savedData);
                this.selectedUser = null;
            }
        } else {
            if (this.selectedGroup) {
                this.selectedGroup.fillData(this.selectedGroup.savedData);
                this.selectedGroup = null;
            }
        }

    };

    findGroupById(groupId: number): Group {

        for (let i = 0, groups = this.groups, length = groups.length; i < length; i++) {
            if (groups[i].ID === groupId) {
                return groups[i];
            }
        }

        return new Group({
            ID: groupId,
            Name: 'Unknown Group ' + groupId
        }, this);

    }

}
