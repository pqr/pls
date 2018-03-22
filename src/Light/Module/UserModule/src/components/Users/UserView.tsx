import * as React from 'react'
import {observer, inject} from 'mobx-react'
import ViewFormField from '../Common/ViewFormField'
import UserModuleStore from "../../UserModuleStore";
import {branchOffices} from "../../Constants";

declare const Light:any;
const userRoles = Light.getPageParam('userRoles');

@inject('store') @observer
class PasswordField extends React.Component<{store?: UserModuleStore}, {showPassword: boolean}> {

    constructor(props: any) {
        super(props);
        this.state = {showPassword: false};
    }

    handleChange = (e: any) => {
        const selectedUser = this.props.store!.selectedUser;
        if (selectedUser) {
            selectedUser.onChange('Password', e.target.value);
        }
    };

    handleShowHideClick = (e: any) => {
        e.preventDefault();
        this.setState({showPassword: !this.state.showPassword})
    };

    render() {

        const selectedUser = this.props.store!.selectedUser;
        if (!selectedUser) {
            return null;
        }

        return (
            <tr>
                <th><label htmlFor="inputPassword">Password</label></th>
                <td>
                    <input type="password"
                           id="inputPassword"
                           value={selectedUser.Password}
                           onChange={this.handleChange}
                    />
                    <div className="usermodule__showhidepassword">
                        {this.state.showPassword
                        && [
                            <input key="1" type="text" className="input-medium" value={selectedUser.Password}
                                   readOnly={true}/>,
                            <a key="2" href="#" className="inner usermodule__hidepasswordlink"
                               onClick={this.handleShowHideClick}>hide</a>
                        ]}

                        {!this.state.showPassword
                        && <a href="#" className="inner" onClick={this.handleShowHideClick}>show/hide</a>}
                    </div>
                </td>
            </tr>
        )

    }

}


@inject('store') @observer
class InnerPhoneField extends React.Component<{store?: UserModuleStore}, {}> {

    handleChange = (e: any) => {
        const selectedUser = this.props.store!.selectedUser;
        if (selectedUser) {
            selectedUser.onChange('InnerPhone', e.target.value);
        }
    };

    render() {

        const selectedUser = this.props.store!.selectedUser;
        if (!selectedUser) {
            return null;
        }

        return (
            <tr>
                <th><label htmlFor="inputInnerPhone">Inner Phone</label></th>
                <td>
                    <input type="text"
                           value={selectedUser.InnerPhone}
                           id="InnerPhone"
                           onChange={this.handleChange}/>
                </td>
            </tr>
        )

    }

}

@inject('store') @observer
class BirthDayMonthField extends React.Component<{store?: UserModuleStore}, {}> {

    handleDayChange = (e: any) => {
        const selectedUser = this.props.store!.selectedUser;
        if (selectedUser) {
            selectedUser.onChange('BirthDay', e.target.value);
        }
    };

    handleMonthChange = (e: any) => {
        const selectedUser = this.props.store!.selectedUser;
        if (selectedUser) {
            selectedUser.onChange('BirthMonth', e.target.value);
        }
    };

    render() {

        const selectedUser = this.props.store!.selectedUser;
        if (!selectedUser) {
            return null;
        }

        return (
            <tr>
                <th><label htmlFor="inputBirthDay">Birth Day / Month</label></th>
                <td>
                    <input type="text"
                           value={selectedUser.BirthDay}
                           id="inputBirthDay"
                           className="input-int input-mini"
                           onChange={this.handleDayChange}/>
                    {' / '}
                    <input type="text"
                           value={selectedUser.BirthMonth}
                           className="input-int input-mini"
                           onChange={this.handleMonthChange}/>
                </td>
            </tr>
        )

    }

}

@inject('store') @observer
class StartedField extends React.Component<{store?: UserModuleStore}, {}> {

    handleChange = (e: any) => {
        const selectedUser = this.props.store!.selectedUser;
        if (selectedUser) {
            selectedUser.onChange('Started', e.target.value);
        }
    };

    render() {

        const selectedUser = this.props.store!.selectedUser;
        if (!selectedUser) {
            return null;
        }

        return (
            <tr>
                <th><label htmlFor="Started">Started (Year)</label></th>
                <td>
                    <input type="number"
                           value={selectedUser.Started}
                           id="Started"
                           onChange={this.handleChange}/>
                </td>
            </tr>
        )

    }

}

@inject('store') @observer
class PositionField extends React.Component<{store?: UserModuleStore}, {}> {

    handleChange = (e: any) => {
        const selectedUser = this.props.store!.selectedUser;
        if (selectedUser) {
            selectedUser.onChange('Position', e.target.value);
        }
    };

    render() {

        const selectedUser = this.props.store!.selectedUser;
        if (!selectedUser) {
            return null;
        }

        return (
            <tr>
                <th><label htmlFor="Position">Position</label></th>
                <td>
                    <input type="text"
                           value={selectedUser.Position}
                           id="Position"
                           onChange={this.handleChange}/>
                </td>
            </tr>
        )

    }

}


@inject('store') @observer
class BranchOfficeField extends React.Component<{store?: UserModuleStore}, {}> {

    handleChange = (e: any) => {
        const selectedUser = this.props.store!.selectedUser;
        if (selectedUser) {
            selectedUser.onChange('BranchOfficeID', e.target.value);
        }
    };

    render() {

        const selectedUser = this.props.store!.selectedUser;
        if (!selectedUser) {
            return null;
        }

        return (
            <tr>
                <th><label htmlFor="inputBranchOffice">Branch Office</label></th>
                <td>
                    <select value={selectedUser.BranchOfficeID}
                            id="inputBranchOffice"
                            onChange={this.handleChange}>
                        <option value=""/>
                        {branchOffices.map(({ID, Name}) => <option value={ID} key={ID}>{Name}</option>)}
                    </select>
                </td>
            </tr>
        )

    }

}

@inject('store') @observer
class GroupField extends React.Component<{store?: UserModuleStore}, {}> {

    handleChange = (e: any) => {
        const selectedUser = this.props.store!.selectedUser;
        if (selectedUser) {
            selectedUser.onChange('GroupID', e.target.value);
        }
    };

    render() {

        const selectedUser = this.props.store!.selectedUser;
        if (!selectedUser) {
            return null;
        }

        return (
            <tr>
                <th><label htmlFor="inputGroup">Group</label></th>
                <td>
                    <select value={selectedUser.GroupID}
                            id="inputGroup"
                            onChange={this.handleChange}>
                        <option value=""/>
                        {this.props.store!.groupsOrderedArray.map(
                            ({ID, Name}) => <option value={ID} key={ID}>{Name}</option>
                        )}
                    </select>
                </td>
            </tr>
        )

    }

}


@inject('store') @observer
class ActiveField extends React.Component<{store?: UserModuleStore}, {}> {

    handleChange = () => {
        const selectedUser = this.props.store!.selectedUser;
        if (selectedUser) {
            selectedUser.onChange('Active', selectedUser.Active ? 0 : 1);
        }
    };

    render() {

        const selectedUser = this.props.store!.selectedUser;
        if (!selectedUser) {
            return null;
        }

        return (
            <tr>
                <th><label htmlFor="inputActive">Active</label></th>
                <td>
                    <input type="checkbox"
                           id="inputActive"
                           checked={selectedUser.Active === 1}
                           onChange={this.handleChange}
                    />
                </td>
            </tr>
        )

    }

}

@inject('store') @observer
class RoleDiv extends React.Component<{store?: UserModuleStore, roleKey: string}, {}> {

    handleClick = () => {
        const selectedUser = this.props.store!.selectedUser;
        if (selectedUser) {
            selectedUser.toggleResponsibleRole(this.props.roleKey);
        }
    };

    render() {

        const selectedUser = this.props.store!.selectedUser;
        if (!selectedUser) {
            return null;
        }

        const roleKey = this.props.roleKey;

        return (
            <div key={roleKey}>
                <input type="checkbox"
                       id={"role" + roleKey}
                       checked={selectedUser.ResponsibleRoles[roleKey]}
                       onChange={this.handleClick}/>
                {' '}
                <label htmlFor={"role" + roleKey}>{userRoles[roleKey]}</label>
            </div>
        )

    }

}

class RolesField extends React.PureComponent<{}, {}> {

    render() {

        let roles: Array<any> = [];
        for (let roleKey in userRoles) {
            roles.push(<RoleDiv key={roleKey} roleKey={roleKey}/>);
        }

        return (
            <tr>
                <th>Roles</th>
                <td>
                    {roles}
                </td>
            </tr>
        )

    }

}


@inject('store') @observer
export default class UserView extends React.Component<{store?: UserModuleStore}, {}> {

    render() {

        const selectedUser = this.props.store!.selectedUser;
        if (!selectedUser) {
            return null;
        }

        return (
            <table className="form">
                <tbody>
                <ViewFormField userOrGroup={selectedUser} fieldKey="Name"/>
                <ViewFormField userOrGroup={selectedUser} fieldKey="Login"/>
                <PasswordField />
                <ViewFormField userOrGroup={selectedUser} fieldKey="Email"/>
                <InnerPhoneField />
                <BirthDayMonthField />
                <StartedField />
                <PositionField />
                <BranchOfficeField />
                <GroupField />
                <ActiveField />
                <RolesField />
                </tbody>
            </table>
        )

    }

}
