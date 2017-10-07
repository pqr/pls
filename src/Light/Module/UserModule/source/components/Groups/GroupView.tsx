import * as React from 'react'
import {observer, inject} from 'mobx-react'
import ViewFormField from '../Common/ViewFormField'
import UserModuleStore from "../../UserModuleStore";

@inject('store') @observer
class OrderField extends React.Component<{store?: UserModuleStore}, {}> {

    handleChange = (e: any) => {
        const selectedGroup = this.props.store!.selectedGroup;
        if (selectedGroup) {
            selectedGroup.onChange('OrderID', e.target.value);
        }
    };

    render() {

        const selectedGroup = this.props.store!.selectedGroup;
        if (!selectedGroup) {
            return null;
        }

        return (
            <tr>
                <th><label htmlFor="inputOrderID">Order</label></th>
                <td>
                    <input type="number"
                           id="inputOrderID"
                           className="input-int input-small"
                           value={selectedGroup.OrderID}
                           onChange={this.handleChange}
                    />
                </td>
            </tr>
        )

    }

}

@inject('store') @observer
export default class GroupView extends React.Component<{store?: UserModuleStore}, {}> {

    render() {

        const selectedGroup = this.props.store!.selectedGroup;
        if (!selectedGroup) {
            return null;
        }

        return (
            <table className="form">
                <tbody>
                <ViewFormField userOrGroup={selectedGroup} fieldKey="Name"/>
                <OrderField />
                </tbody>
            </table>
        )

    }

}