import * as React from "react";
import {computed} from "mobx";
import {observer, inject} from "mobx-react";
import {Group} from "../../classes/Group";
import UserModuleStore from "../../UserModuleStore";

@inject('store') @observer
class GroupTr extends React.Component<{group: Group, store?: UserModuleStore}, {}> {

    @computed get isSelected() {
        return this.props.store!.selectedGroup === this.props.group;
    }

    handleRowClick = () => {
        this.props.store!.selectGroup(this.props.group);
    };

    render() {

        let trClassName = '';
        if (this.isSelected) {
            trClassName = 'selected';
        }

        return (
            <tr className={trClassName} onClick={this.handleRowClick}>
                <td>{this.props.group.Name}</td>
            </tr>
        )

    }

}

@inject('store') @observer
class GroupsTbody extends React.Component<{store?: UserModuleStore}, {}> {

    render() {

        return (
            <tbody>
            {this.props.store!.groupsOrderedArray.map((group: Group) => <GroupTr key={group.ID} group={group}/>)}
            </tbody>
        );

    }

}

export default class GroupsTable extends React.PureComponent<{}, {}> {

    render() {

        return (
            <table className="table nosticky">
                <thead><tr><th>Name</th></tr></thead>
                <GroupsTbody />
            </table>
        );

    }

}
