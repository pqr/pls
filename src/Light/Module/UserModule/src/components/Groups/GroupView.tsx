import * as React from "react";
import { observer, inject } from "mobx-react";
import UserModuleStore from "../../UserModuleStore";

interface GroupFieldProps {
    store?: UserModuleStore;
    fieldKey: string;
    label: string;
    type?: string;
}

@inject("store")
@observer
class GroupField extends React.Component<GroupFieldProps> {
    static defaultProps = {
        type: "text"
    };

    handleChange = (e: any) => {
        this.props.store!.selectedGroup!.onChange(this.props.fieldKey, e.target.value);
    };

    render() {
        const { fieldKey, label } = this.props;
        const value = this.props.store!.selectedGroup![fieldKey];
        const inputId = "id" + fieldKey;

        return (
            <tr>
                <th>
                    <label htmlFor={inputId}>{label}</label>
                </th>
                <td>
                    <input type={this.props.type} onChange={this.handleChange} value={value} id={inputId} />
                </td>
            </tr>
        );
    }
}

@inject("store")
@observer
export default class GroupView extends React.Component<{ store?: UserModuleStore }, {}> {
    render() {
        const selectedGroup = this.props.store!.selectedGroup;
        if (!selectedGroup) {
            return null;
        }

        return (
            <table className="form">
                <tbody>
                    <GroupField fieldKey="Name" label="Name" />
                    <GroupField fieldKey="OrderID" label="OrderID" type="number" />
                </tbody>
            </table>
        );
    }
}
