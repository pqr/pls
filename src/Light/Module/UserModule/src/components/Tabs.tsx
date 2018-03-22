import * as React from 'react'
import {observer, inject} from 'mobx-react';
import UserModuleStore from "../UserModuleStore";

@inject('store') @observer
export default class Tabs extends React.Component<{store?:UserModuleStore}, {}> {

    onUsersTabClick = () => {
        this.props.store!.chageTab('user');
    };

    onGroupsTabClick = () => {
        this.props.store!.chageTab('group');
    };

    render() {

        const store = this.props.store!;

        return (
            <ul className="usermodule__tabs">
                <li className={'usermodule__tabusers' + (store.isUsersTabActive ? '_active' : '')}
                    onClick={this.onUsersTabClick}>
                    Users
                </li>
                <li className={'usermodule__tabgroups' + (store.isGroupsTabActive ? '_active' : '')}
                    onClick={this.onGroupsTabClick}>
                    Groups
                </li>
            </ul>
        )

    }

}