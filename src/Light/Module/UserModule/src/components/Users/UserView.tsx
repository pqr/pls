import * as React from "react";
import { observer, inject } from "mobx-react";
import UserModuleStore from "../../UserModuleStore";
import { branchOffices } from "../../Constants";

declare const Light: any;
const userRoles = Light.getPageParam("userRoles");

interface UserFieldProps {
    store?: UserModuleStore;
    fieldKey: string;
    label: string;
    render: (handleChange: any, value: any, inputId: string) => any;
}

/** Используем паттерн render props */
@inject("store")
@observer
class UserField extends React.Component<UserFieldProps> {
    handleChange = (e: any) => {
        this.props.store!.selectedUser!.onChange(this.props.fieldKey, e.target.value);
    };

    render() {
        const { fieldKey, label } = this.props;
        const value = this.props.store!.selectedUser![fieldKey];
        const inputId = "id" + fieldKey;

        return (
            <tr>
                <th>
                    <label htmlFor={inputId}>{label}</label>
                </th>
                <td>{this.props.render(this.handleChange, value, inputId)}</td>
            </tr>
        );
    }
}

interface UserFieldTextProps {
    fieldKey: string;
    label: string;
    type?: string;
}

class UserFieldText extends React.Component<UserFieldTextProps> {
    static defaultProps = {
        type: "text"
    };

    render() {
        return (
            <UserField
                fieldKey={this.props.fieldKey}
                label={this.props.label}
                render={(handleChange, value, inputId) => (
                    <input type={this.props.type} onChange={handleChange} value={value} id={inputId} />
                )}
            />
        );
    }
}

@inject("store")
@observer
class PasswordField extends React.Component<{ store?: UserModuleStore }, { showPassword: boolean }> {
    constructor(props: any) {
        super(props);
        this.state = { showPassword: false };
    }

    handleChange = (e: any) => {
        this.props.store!.selectedUser!.onChange("Password", e.target.value);
    };

    handleShowHideClick = (e: any) => {
        e.preventDefault();
        this.setState({ showPassword: !this.state.showPassword });
    };

    render() {
        const selectedUser = this.props.store!.selectedUser!;

        return (
            <tr>
                <th>
                    <label htmlFor="inputPassword">Password</label>
                </th>
                <td>
                    <input
                        type="password"
                        id="inputPassword"
                        value={selectedUser.Password}
                        onChange={this.handleChange}
                    />
                    <div className="usermodule__showhidepassword">
                        {this.state.showPassword && [
                            <input
                                key="1"
                                type="text"
                                className="input-medium"
                                value={selectedUser.Password}
                                readOnly={true}
                            />,
                            <a
                                key="2"
                                href="#"
                                className="inner usermodule__hidepasswordlink"
                                onClick={this.handleShowHideClick}
                            >
                                hide
                            </a>
                        ]}

                        {!this.state.showPassword && (
                            <a href="#" className="inner" onClick={this.handleShowHideClick}>
                                show/hide
                            </a>
                        )}
                    </div>
                </td>
            </tr>
        );
    }
}

@inject("store")
@observer
class BirthDayMonthField extends React.Component<{ store?: UserModuleStore }, {}> {
    handleDayChange = (e: any) => {
        this.props.store!.selectedUser!.onChange("BirthDay", e.target.value);
    };

    handleMonthChange = (e: any) => {
        this.props.store!.selectedUser!.onChange("BirthMonth", e.target.value);
    };

    render() {
        const selectedUser = this.props.store!.selectedUser!;
        const day = selectedUser.BirthDay || "";
        const month = selectedUser.BirthMonth || "";

        return (
            <tr>
                <th>
                    <label htmlFor="inputBirthDay">Birth Day / Month</label>
                </th>
                <td>
                    <input
                        type="number"
                        value={day}
                        min="1"
                        max="31"
                        id="inputBirthDay"
                        className="input-mini"
                        onChange={this.handleDayChange}
                    />
                    {" / "}
                    <input
                        type="number"
                        min="1"
                        max="12"
                        value={month}
                        className="input-mini"
                        onChange={this.handleMonthChange}
                    />
                </td>
            </tr>
        );
    }
}

@inject("store")
class SexField extends React.Component<{ store?: UserModuleStore }, {}> {
    render() {
        return (
            <UserField
                fieldKey="Sex"
                label="Sex"
                render={(handleChange, value, inputId) => (
                    <select value={value} id={inputId} onChange={handleChange}>
                        <option value="" />
                        <option value="M">M</option>
                        <option value="F">F</option>
                    </select>
                )}
            />
        );
    }
}

class BranchOfficeField extends React.Component {
    render() {
        return (
            <UserField
                fieldKey="BranchOfficeID"
                label="Branch Office"
                render={(handleChange, value, inputId) => (
                    <select value={value} id={inputId} onChange={handleChange}>
                        <option value="" />
                        {branchOffices.map(({ ID, Name }) => (
                            <option value={ID} key={ID}>
                                {Name}
                            </option>
                        ))}
                    </select>
                )}
            />
        );
    }
}

@inject("store")
class GroupField extends React.Component<{ store?: UserModuleStore }, {}> {
    render() {
        return (
            <UserField
                fieldKey="GroupID"
                label="Group"
                render={(handleChange, value, inputId) => (
                    <select value={value} id={inputId} onChange={handleChange}>
                        {this.props.store!.selectedUser!.ID == 0 && <option value="" />}
                        {this.props.store!.groupsOrderedArray.map(({ ID, Name }) => (
                            <option value={ID} key={ID}>
                                {Name}
                            </option>
                        ))}
                    </select>
                )}
            />
        );
    }
}

@inject("store")
@observer
class ActiveField extends React.Component<{ store?: UserModuleStore }, {}> {
    handleChange = () => {
        const selectedUser = this.props.store!.selectedUser!;
        selectedUser.onChange("Active", selectedUser.Active ? 0 : 1);
    };

    render() {
        return (
            <tr>
                <th>
                    <label htmlFor="inputActive">Active</label>
                </th>
                <td>
                    <input
                        type="checkbox"
                        id="inputActive"
                        checked={this.props.store!.selectedUser!.Active === 1}
                        onChange={this.handleChange}
                    />
                </td>
            </tr>
        );
    }
}

@inject("store")
@observer
class RoleDiv extends React.Component<{ store?: UserModuleStore; roleKey: string }, {}> {
    handleClick = () => {
        const selectedUser = this.props.store!.selectedUser!;
        selectedUser.toggleResponsibleRole(this.props.roleKey);
    };

    render() {
        const selectedUser = this.props.store!.selectedUser!;
        const roleKey = this.props.roleKey;

        return (
            <div key={roleKey}>
                <input
                    type="checkbox"
                    id={"role" + roleKey}
                    checked={selectedUser.ResponsibleRoles[roleKey]}
                    onChange={this.handleClick}
                />{" "}
                <label htmlFor={"role" + roleKey}>{userRoles[roleKey]}</label>
            </div>
        );
    }
}

class RolesField extends React.PureComponent<{}, {}> {
    render() {
        let roles: any[] = [];
        for (let roleKey in userRoles) {
            roles.push(<RoleDiv key={roleKey} roleKey={roleKey} />);
        }

        return (
            <tr>
                <th>Roles</th>
                <td>{roles}</td>
            </tr>
        );
    }
}

@inject("store")
@observer
export default class UserView extends React.Component<{ store?: UserModuleStore }, {}> {
    render() {
        const selectedUser = this.props.store!.selectedUser;
        if (!selectedUser) {
            return null;
        }

        return (
            <table className="form">
                <tbody>
                    <UserFieldText fieldKey="Name" label="Name" />
                    <UserFieldText fieldKey="Login" label="Login" />
                    <PasswordField />
                    <UserFieldText fieldKey="Email" label="Email" />
                    <UserFieldText fieldKey="InnerPhone" label="Inner Phone" />
                    <UserFieldText fieldKey="Mobile" label="Mobile" />
                    <BirthDayMonthField />
                    <SexField />
                    <UserFieldText fieldKey="Started" label="Started (Year)" type="number" />
                    <UserFieldText fieldKey="Position" label="Position" />
                    <BranchOfficeField />
                    <GroupField />
                    <ActiveField />
                    <RolesField />
                </tbody>
            </table>
        );
    }
}
