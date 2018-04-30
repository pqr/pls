import * as React from 'react'
import {computed} from 'mobx'
import {observer, inject} from 'mobx-react'
import {Module, modulesPerSection, SectionInfo, SpecialAccessInfo} from "../Constants"
import UserModuleStore from "../UserModuleStore";
import GroupView from "./Groups/GroupView";
import UserView from "./Users/UserView";

const accessDescriptions = ['Deny', 'Read', 'Write', 'Write + Delete'];
const specialAccessDescriptions = ['No', 'Yes'];

function getAccessClassName(access: number) {
    switch (access) {
        case 0 :
            return 'bad';
        case 1 :
            return 'read';
        case 2:
            return 'good';
        case 3:
            return 'good bold';

    }

    return '';
}

function getSpecialAccessClassName(access: number) {
    return access === 0 ? 'no' : 'yes';
}

@inject('store') @observer
class ViewHeader extends React.Component<{store?: UserModuleStore}, {}> {

    @computed get selected() {
        const store = this.props.store!;
        return store.isUsersTabActive ? store.selectedUser : store.selectedGroup;
    }

    @computed get isExistUserSelected() {
        const store = this.props.store!;
        return store.isUsersTabActive && this.selected && this.selected.ID != 0;
    }

    @computed get isChanged(){
        return this.selected && this.selected.isChanged;
    }

    render() {

        const store = this.props.store!;

        const id = this.selected ? this.selected.ID : 0;

        return (<div>
            <h1 id="page-head">
                {!id ? 'New ' + store.tab : store.tab + ' ID ' + id}
                {this.isChanged && <span>*</span>}
            </h1>
            {this.isExistUserSelected &&
            <a href={"?a=UserModule&p=ViewHome&userId=" + id}
               className="btn-flat waves-effect usermodule__homelink" target="_blank">Home</a>
            }
            {this.isExistUserSelected &&
            <a href={"?a=UserModule&p=LoginAs&userId=" + id}
               className="btn-flat waves-effect usermodule__loginlink">Login</a>
            }
        </div>)

    }
}

@inject('store') @observer
class SpecialAccessDiv extends React.Component<{store?: UserModuleStore, accessKey: string, description: string}, {}> {

    handleSpecialAccessChange = (e: any) => {

        const newAccess = parseInt(e.target.value);
        const store = this.props.store!;
        if (store.isUsersTabActive) {
            store.selectedUser!.onChangeSpecialAccess(this.props.accessKey, newAccess);
        } else {
            store.selectedGroup!.onChangeSpecialAccess(this.props.accessKey, newAccess);
        }

    };

    render() {

        const store = this.props.store!;
        const {accessKey, description} = this.props;

        let currentSpecialAccess, selectClassName, inheritClassName, groupAccessDescription;
        if (store.isUsersTabActive) {

            const selectedUser = store.selectedUser!;
            currentSpecialAccess = selectedUser.specialAccess[accessKey];
            const groupSpecialAccess = store.findGroupById(selectedUser.GroupID).specialAccess[accessKey];
            groupAccessDescription = specialAccessDescriptions[groupSpecialAccess];
            inheritClassName = getAccessClassName(groupSpecialAccess);
            selectClassName = currentSpecialAccess == -1 ? inheritClassName : getSpecialAccessClassName(currentSpecialAccess);

        } else {

            currentSpecialAccess = store.selectedGroup!.specialAccess[accessKey];
            selectClassName = getAccessClassName(currentSpecialAccess);
        }

        return (
            <div key={accessKey}>

                <select className={"usermodule__accessselect " + selectClassName}
                        value={currentSpecialAccess.toString()}
                        onChange={this.handleSpecialAccessChange}>

                    {store.isUsersTabActive
                    && <option value="-1" className={inheritClassName}>Inherit: {groupAccessDescription}</option>}

                    <option value="0" className="no">No</option>
                    <option value="1" className="yes">Yes</option>
                </select>
                {' '}
                {description}



            </div>
        );

    }

}

@inject('store') @observer
class ViewAccessRow extends React.Component<{store?: UserModuleStore, module: Module}, {}> {

    renderSpecialAccess = ({accessKey, description}:SpecialAccessInfo) => {

        return <SpecialAccessDiv key={accessKey} accessKey={accessKey} description={description}/>

    };

    handleModuleAccessChange = (e: any) => {

        const newAccess = parseInt(e.target.value);
        const store = this.props.store!;
        if (store.isUsersTabActive) {
            store.selectedUser!.onChangeModuleAccess(this.props.module.key, newAccess);
        } else {
            store.selectedGroup!.onChangeModuleAccess(this.props.module.key, newAccess);
        }

    };

    render() {

        const store = this.props.store!;
        const module = this.props.module;

        let currentAccess, selectClassName, inheritClassName, groupAccessDescription;
        if (store.isUsersTabActive) {

            const selectedUser = store.selectedUser!;
            currentAccess = selectedUser.moduleAccess[module.key];
            const groupAccess = store.findGroupById(selectedUser.GroupID).moduleAccess[module.key];
            groupAccessDescription = accessDescriptions[groupAccess];
            inheritClassName = getAccessClassName(groupAccess);
            selectClassName = currentAccess == -1 ? inheritClassName : getAccessClassName(currentAccess);

        } else {

            currentAccess = store.selectedGroup!.moduleAccess[module.key];
            selectClassName = getAccessClassName(currentAccess);
        }


        return (
            <tr>
                <th>{module.name}</th>
                <td>
                    <select
                        className={"usermodule__accessselect " + selectClassName}
                        value={currentAccess.toString()}
                        onChange={this.handleModuleAccessChange}>

                        {store.isUsersTabActive
                        && <option value="-1" className={inheritClassName}>Inherit: {groupAccessDescription}</option>}

                        <option value="0" className={getAccessClassName(0)}>{accessDescriptions[0]}</option>
                        <option value="1" style={{color: 'black'}} className={getAccessClassName(1)}>{accessDescriptions[1]}</option>
                        <option value="2" className={getAccessClassName(2)}>{accessDescriptions[2]}</option>
                        <option value="3" className={getAccessClassName(3)}>{accessDescriptions[3]}</option>

                    </select>
                </td>
                <td>
                    {module.specialAccessList.map(this.renderSpecialAccess)}
                </td>
            </tr>
        )
    }

}

@inject('store') @observer
class ViewAccessChangeAllButtons extends React.Component<{store?: UserModuleStore}, {}> {

    clickHandler(newAccess: number) {

        const store = this.props.store!;
        if (store.isUsersTabActive) {
            store.selectedUser!.changeAccessAll(newAccess);
        } else {
            store.selectedGroup!.changeAccessAll(newAccess);
        }

    }

    render() {

        const store = this.props.store!;

        return (
            <div className="usermodule__viewaccessallbuttons">
                <strong>Change all:</strong>
                {store.isUsersTabActive
                &&
                <span className="usermodule__accesschangeall" onClick={() => this.clickHandler(-1)}>All Inherit</span>}
                <span className="usermodule__accesschangeall" onClick={() => this.clickHandler(0)}>All {accessDescriptions[0]}</span>
                <span className="usermodule__accesschangeall" onClick={() => this.clickHandler(1)}>All {accessDescriptions[1]}</span>
                <span className="usermodule__accesschangeall" onClick={() => this.clickHandler(2)}>All {accessDescriptions[2]}</span>
                <span className="usermodule__accesschangeall" onClick={() => this.clickHandler(3)}>All {accessDescriptions[3]}</span>
            </div>
        )

    }

}

class ViewAccess extends React.Component<{store?: UserModuleStore}, {}> {

    getRows() {
        let rows: any[] = [];
        modulesPerSection.forEach((sectionInfo: SectionInfo, index: number) => {
            const {section, modules} = sectionInfo;
            rows.push(<tr key={index}>
                <th><strong>{section}</strong></th>
            </tr>);
            modules.forEach((module: Module) => {
                rows.push(<ViewAccessRow key={module.key} module={module}/>);
            });
        });

        return rows;
    };

    render() {

        return (
            <div className="usermodule__accessblock">

                <table className="form usermodule__accesstable">
                    <tbody>
                    {this.getRows()}
                    </tbody>
                </table>

                <ViewAccessChangeAllButtons />
            </div>
        )

    }
}

@inject('store') @observer
class ViewFormActions extends React.Component<{store?:UserModuleStore}, {}> {

    render() {

        const store = this.props.store!;
        const userOrGroup = store.isUsersTabActive ? store.selectedUser : store.selectedGroup;
        if (!userOrGroup) {
            return null;
        }

        return (
            <div className="formactions_fixed">
                <span
                    className={"waves-effect btn bgcolor-btn-primary" + (userOrGroup.isSaving ? ' disabled' : '')}
                    title="Ctrl + S"
                    onClick={store.save}
                >Save</span>

                <a href="#" className="btn-flat waves-effect cancel-link"
                   onClick={store.cancel}
                >Cancel</a>
            </div>
        )
    }

}

@inject('store') @observer
class View extends React.Component<{store?: UserModuleStore}, {}> {

    render() {

        const store = this.props.store!;

        if (store.isUsersTabActive && !store.selectedUser) {
            return null;
        }

        if (store.isGroupsTabActive && !store.selectedGroup) {
            return null;
        }

        return (
            <div>

                <div style={{paddingLeft: 10, marginBottom: 100}}>

                    <ViewHeader />

                    {store.isUsersTabActive ? <UserView /> : <GroupView />}

                    <ViewAccess />

                    <ViewFormActions />

                </div>

            </div>
        );

    }

}

export default View;
