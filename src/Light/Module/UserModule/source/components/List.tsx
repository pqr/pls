import * as React from "react";
import {observer, inject} from "mobx-react";
import UserModuleStore from "../UserModuleStore";
import Tabs from "./Tabs";
import AddNewBtn from "./Common/AddNewBtn";
import UsersTable from "./Users/UsersTable";
import UsersFilters from "./Users/UsersFilters";
import GroupsTable from "./Groups/GroupsTable";

@inject('store') @observer
class List extends React.Component<{store?:UserModuleStore}, {}> {

    onUserAddNewClick = () => {
        this.props.store!.addNewUser();
    };

    onGroupAddNewClick = () => {
        this.props.store!.addNewGroup();
    };

    render() {

        const store = this.props.store!;

        return (
            <div>
                <Tabs />

                <div className={store.isUsersTabActive ? '' : 'display-none'}>
                    <AddNewBtn text="User" onClick={this.onUserAddNewClick} />
                    <UsersFilters />
                    <UsersTable />
                </div>

                <div className={store.isGroupsTabActive ? '' : 'display-none'}>
                    <AddNewBtn text="Group" onClick={this.onGroupAddNewClick} />
                    <GroupsTable />
                </div>
            </div>
        );

    }

}

export default List;