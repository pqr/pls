import * as React from "react";
import {computed} from "mobx";
import {observer, inject} from "mobx-react";
import {User} from "../../classes/User";
import UserModuleStore from "../../UserModuleStore";

@inject('store') @observer
class UserTr extends React.Component<{user: User, store?: UserModuleStore}, {}> {

    @computed get isSelected() {
        return this.props.store!.selectedUser === this.props.user;
    }

    @computed get GroupName() {
        return this.props.store!.findGroupById(this.props.user.GroupID).Name;
    }

    handleRowClick = () => {
        this.props.store!.selectUser(this.props.user);
    };

    render() {

        const user = this.props.user;

        let trClassName = '';
        if (!user.Active) {
            trClassName += ' usermodule__listrow_inactive';
        }
        if (this.isSelected) {
            trClassName += ' selected';
        }

        return (
            <tr className={trClassName} onClick={this.handleRowClick}>
                <td>
                    {user.Name}
                    {user.isChanged && <strong className="bad">*</strong>}
                </td>
                <td>{this.GroupName}</td>
                <td>{user.Idle}</td>
            </tr>
        )

    }

}


@inject('store') @observer
class UsersTbody extends React.Component<{store?: UserModuleStore}, {}> {

    render() {

        return (
            <tbody>
            {this.props.store!.visibleUsers.map((user: User) => <UserTr key={user.ID} user={user}/>)}
            </tbody>
        );

    }

}

@inject('store')
export default class UsersTable extends React.PureComponent<{store?: UserModuleStore}, {}> {

    render() {

        return (
            <table className="table nosticky">
                <thead>
                <tr>
                    <th onClick={() => this.props.store!.changeUsersSort('Name')}>Name</th>
                    <th onClick={() => this.props.store!.changeUsersSort('GroupUD')}>Group</th>
                    <th onClick={() => this.props.store!.changeUsersSort('IdleTimestamp')}>Idle</th>
                </tr>
                </thead>
                <UsersTbody />
            </table>
        );

    }

}